<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Media;
use App\Service\FileUploader;
use OpenApi\Annotations as OA;
use App\Repository\UserRepository;
use App\Repository\MediaRepository;
use League\Flysystem\FilesystemOperator;
use Doctrine\Persistence\ManagerRegistry;
use App\Controller\Api\ApiAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Les user ROLES_EDITOR, par les routes api_medias_editorial_xxx gèrent les medias via un user spécifique (id défini dans .env)
 * ils n'ont pas de limite totale de fichiers (mais toujours les 2Mo par fichier)
 */
class ApiMediaController extends ApiAbstractController
{
    private $uploadMaxSize = 2*1024*1024; //2Mo

    private $mediaRepository;
    private $userRepository;
    private $imageStorage;
    private $defaultStorage;

    public function __construct(
        MediaRepository $mediaRepository, 
        UserRepository $userRepository,
        FilesystemOperator $imageStorage,
        FilesystemOperator $defaultStorage
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->userRepository = $userRepository;
        $this->imageStorage = $imageStorage;
        $this->defaultStorage = $defaultStorage;
        
    }

    /**
     * @Route("/editorial/medias", name="api_media_editorial_get", methods={"GET"})
     * @Route("/api/medias", name="api_media_get", methods={"GET"})
     * @Route("/admin/api/medias", name="admin_api_media_get", methods={"GET"}, options={"expose"=true})
     * 
     * @OA\Get(
     *      tags={"Media"},
     *      path="/api/medias",
     *      security={"bearer"},
     *      description="Renvoie les medias de l'utilisateur connecté",
     *      @OA\Parameter(
     *          name="name",
     *          in="query",
     *          description="Name contient cette valeur",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          in="query",
     *          description="Identifiant du média recherché",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="valid",
     *          in="query",
     *          description="Si 'true', limite la recherche aux médias invalidés par les administrateurs",
     *          required=false,
     *          @OA\Schema(type="boolean")
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="tri des medias, parmi ['size', 'date']",
     *          required=false,
     *          @OA\Schema(type="boolean", default="date")
     *      ),
     *      @OA\Parameter(ref="#/components/parameters/offset"),
     *      @OA\Parameter(ref="#/components/parameters/limit"),
     *      @OA\Response(
     *          response="200",
     *          description="liste des medias",
     *          @OA\JsonContent(
     *              type="array", 
     *              @OA\Items(ref="#/components/schemas/Media_list")
     *          ),
     *      ),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="403", ref="#/components/responses/Forbidden")
     * )
     */
    public function research(Request $request, SerializerInterface $serializer): Response
    {
        if($request->get('_route') == 'admin_api_media_get'){
            $userId = null;
            if($request->get('username')){
                $user = $this->userRepository->findOneByUsername($request->get('username'));
                if(!$user){
                    return $this->returnResponse('user not found', Response::HTTP_NOT_FOUND);

                }
                $userId = $user->getId();
            }
        }elseif($request->get('_route') == 'api_media_editorial_get'){
            if(!$this->isGranted('ROLE_EDITOR')){
                return $this->returnResponse('forbidden', Response::HTTP_FORBIDDEN);
            }
            $userId = $this->getParameter('user_editor_default_id');
        }else{
            /** @var User $user */
            $user = $this->getUser();
            if(!$user){
                return $this->returnResponse('not connected', Response::HTTP_UNAUTHORIZED);
            }
            $userId = $user->getId();
        }

        $limit = intval($request->get('limit')) ?: 15;
        $offset = intval($request->get('offset')) ?: 0;

        $criteria = array(
            'userId' => $userId, 
            'id' => $request->get('id'),
            'name' => $request->get('name'),
            'folder' => $request->get('folder'),
            'valid' => $request->get('valid') == "false" ? false : null,
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $request->get('sort'),
        );

        $result = $this->mediaRepository->research($criteria);
        
        $medias = array_merge($result, array('normalizer' => 'media'));
        
        $json = $serializer->serialize($medias, 'json');

        if($result['count'] <= $limit){
            return new Response($json, Response::HTTP_OK, array(
                'Content-Type' => 'application/json',
            ));
        }

        $response = new Response($json, Response::HTTP_PARTIAL_CONTENT);

        $linkHeader = '';
        $uri = $request->getUri();
        if($offset > 0){
            $urlFirst = Request::create($uri, 'GET', array(
                'offset' => 0
            ));
            $linkHeader .= '<'.$urlFirst->getUri().'>; rel="first",';
        }
        
        $urlNext = Request::create($uri, 'GET', array(
            'offset' => $offset + $limit
        ));
        $linkHeader .= '<'.$urlNext->getUri().'>; rel="next",';
        
        $response->headers->add(array(
            'Content-Range' => $offset.'-'.($offset + $limit - 1) .'/'.$result['count'],
            'Link' => $linkHeader,
            'Content-Type' => 'application/json',
        ));

        return $response;
    }

    /**
     * @Route("/editorial/medias/folders", name="api_media_editorial_folders", methods={"GET"})
     * @Route("/api/medias/folders", name="api_media_folder", methods={"GET"})
     * 
     * @OA\Get(
     *      tags={"Media"},
     *      path="/api/medias/folders",
     *      security={"bearer"},
     *      description="Renvoie dossiers d'images de l'utilsiateur",
     *      @OA\Response(
     *          response="200",
     *          description="liste des dossiers d'images",
     *          @OA\JsonContent(
     *              type="array", 
     *              @OA\Items(type="string"),
     *          ),
     *      ),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     * )
     */
    public function getFolders(Request $request): Response
    {
        if($request->get('_route') == 'api_media_editorial_folders'){
            if(!$this->isGranted('ROLE_EDITOR')){
                return $this->returnResponse('forbidden', Response::HTTP_FORBIDDEN);
            }
            $userId = $this->getParameter('user_editor_default_id');
        }else{
            /** @var User $user */
            $user = $this->getUser();
            if(!$user){
                return $this->returnResponse('not connected', Response::HTTP_UNAUTHORIZED);
            }
            $userId = $user->getId();
        }
        $folders = $this->mediaRepository->findFolders($userId);
        return new JsonResponse($folders, Response::HTTP_OK);
    }

    /**
     * @Route("/api/image/{filename}", name="api_media_view_image", options={"expose"=true})
     * @Route("/image/voir/{filename}", name="media_view_image", options={"expose"=true})
     * 
     * @OA\Get(
     *      tags={"Media"},
     *      path="/api/image/{filename}",
     *      description="Renvoie l'image au format binaire, l'extension n'est pas prise en compte (ex : abc1234) <br>
                Préfixer le filename par 'thumb_' pour recevoir la miniature (200px sur la plus grande dimension)
     *      ",
     *      @OA\Parameter(
     *          name="filename",
     *          in="path",
     *          description="Nom du fichier",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Fichier binaire de l'image"
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     *      @OA\Response(response="451", ref="#/components/responses/Invalid"),
     * )
     */
    public function view(Request $request, string $filename):Response
    {
        $responseType = $request->get('error') == 'json' ? 'json' : 'image';

        $isThumb = substr( $filename, 0, 6 ) === "thumb_" ? true : false;

        //on enlève le prefixe thumb_ ou img_ ,  $filename = abc123[.png]
        $filename = preg_replace('/^(thumb_|img_)/', '', $filename);

        //on enlève l'extension si elle est renseignée
        $filename = explode('.', $filename)[0];

        /** @var Media $media */
        $media = $this->mediaRepository->findOneBy(['filename' => $filename]);
        
        // media non trouvé
        if(!$media){
            if($responseType == 'json'){
                return $this->returnResponse('not found', Response::HTTP_NOT_FOUND);
            }
            if($isThumb){
                $stream = $this->defaultStorage->readStream('images/thumb_notfound.png');
            }else{
                $stream = $this->defaultStorage->readStream('images/image_notfound.png');
            }
            $meta = stream_get_meta_data($stream);
            $uri = $meta['uri'];

            return $this->file($uri, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
        }

        // media invalide
        if( !$media->isValid()
            and $media->getOwner() !== $this->getUser() 
            and ( !$this->isGranted('ROLE_SUPER_ADMIN') )
        ){
            if($responseType == 'json'){
                return $this->returnResponse('invalid', Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS);
            }
            if($isThumb){
                $stream = $this->defaultStorage->readStream('images/thumb_invalid.png');
            }else{
                $stream = $this->defaultStorage->readStream('images/image_invalid.png');
            }
            $meta = stream_get_meta_data($stream);
            $uri = $meta['uri'];

            return $this->file($uri, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
        }

        if($isThumb){
            if($this->imageStorage->fileExists($media->getThumb())){
                $stream = $this->imageStorage->readStream($media->getThumb());
            }else{
                $stream = $this->defaultStorage->readStream('images/thumb_notfound.png');
            }
        }else{
            if($this->imageStorage->fileExists($media->getFile())){
                $stream = $this->imageStorage->readStream($media->getFile());
            }else{
                $stream = $this->defaultStorage->readStream('images/image_notfound.png');
            }
        }
        
        $response =  new StreamedResponse(function() use($stream){
            $outputStream = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $outputStream);
        });

        switch($media->getExtension()){
            case 'png': 
                $mimetype = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $mimetype = 'image/jpeg';
                break;
            case 'gif':
                $mimetype = 'image/gif';
                break;
            case 'svg':
                $mimetype = 'image/svg+xml';
                break;
        }
        $response->headers->set('Content-Type', $mimetype);

        return $response;
    }

    /**
     * @Route("/editorial/medias", name="api_media_editorial_add", methods={"POST"})
     * @Route("/api/medias", name="api_media_add", methods={"POST"})
     * 
     * @OA\Post(
     *      tags={"Media"},
     *      path="/api/medias",
     *      security={"bearer"},
     *      description="Ajoute un media à l'utilisateur connecté",
     *      @OA\Parameter(
     *          name="file",
     *          in="formData",
     *          required=true,
     *          description="image à ajouter à la bibliothèque, le fichier doit faire moins de 2 Mo",
     *          @OA\Schema(type="file", format="png|jpg|jpeg|gif")
     *      ),
     *      @OA\Parameter(
     *          name="folder",
     *          in="formData",
     *          description="Dossier où ajouter l'image",
     *          @OA\Schema(type="string", default="Non classé")
     *      ),
     *      @OA\Parameter(
     *          name="name",
     *          in="formData",
     *          description="Nom du fichier",
     *          @OA\Schema(type="string", default="original_name du fichier")
     *      ),
     *      @OA\Response(
     *          response="201",
     *          description="Attributs du media créé",
     *          @OA\JsonContent(ref="#/components/schemas/Media")
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/BadRequest"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="413", ref="#/components/responses/TooLarge"),
     * )
     */
    public function add(Request $request, SerializerInterface $serializer): Response
    {
        /** @var User $user */

        if($request->get('_route') == 'api_media_editorial_add'){
            if(!$this->isGranted('ROLE_EDITOR')){
                return $this->returnResponse('forbidden', Response::HTTP_FORBIDDEN);
            }
            $user = $this->userRepository->find($this->getParameter('user_editor_default_id'));
        }else{
            $user = $this->getUser();
        }

        $file = $this->checkFile($request->files->get('file'));
        if($file instanceof Response){
            //une erreur a été détectée dans la requete
            return $file;
        }

        if($request->get('_route') !== 'api_media_editorial_add'){
            $checkSize = $this->checkSize($user, $file);
            if($checkSize instanceof Response){
                return $checkSize;
            }
        }

        // création du média
        $media = (new Media())
            ->setOwner($user)
            ->setName($request->get('name') ?: $file->getClientOriginalName())
            ->setSize(filesize($file))
            ->setFile('temp')
            ->setFolder($request->get('folder') ?: 'Non classé')
        ;
        $this->mediaRepository->persist($media);
        
        $extension = $file->guessExtension();
        // définition du nouveau nom de fichier
        $alpha = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
        $newFilename = $alpha[rand(0,25)].$alpha[rand(0,25)].$alpha[rand(0,25)].$media->getId();

        $media->setFile($newFilename.'.'.$extension);
        $media->setThumb('thumb_'.$newFilename.'.'.$extension);
        $media->setFilename($newFilename);
        $media->setExtension($extension);
        $this->mediaRepository->persist($media);
        
        $this->writeFiles($file, $newFilename.'.'.$extension);

        $json = $serializer->serialize($media, 'json');

        return new Response($json, Response::HTTP_CREATED, array(
            'Content-Type' => 'application/json',
        ));

    }

    /**
     * @Route("/editorial/medias/{id}", name="api_media_editorial_delete", methods={"DELETE"})
     * @Route("/api/medias/{id}", name="api_media_delete", methods={"DELETE"})
     * @Route("/admin/api/medias/{id}", name="admin_api_media_delete", methods={"DELETE"}, options={"expose"=true})
     * 
     * @OA\Delete(
     *      tags={"Media"},
     *      path="/api/medias/{id}",
     *      security={"bearer"},
     *      description="Supprime un media de l'utilisateur connecté. Un média non valide ne peut pas être supprimé",
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Response(response="204", ref="#/components/responses/Deleted"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="403", ref="#/components/responses/Forbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     *      @OA\Response(response="451", ref="#/components/responses/Invalid"),
     * )
     */
    public function delete($id, Request $request): Response
    {
        /** @var User $user */

        if($request->get('_route') == 'api_media_editorial_delete'){
            if(!$this->isGranted('ROLE_EDITOR')){
                return $this->returnResponse('forbidden', Response::HTTP_FORBIDDEN);
            }
            $user = $this->userRepository->find($this->getParameter('user_editor_default_id'));
        }else{
            $user = $this->getUser();
        }

        /** @var MediaRepository $repository */
        $media = $this->mediaRepository->find($id);

        if(!$media){
            return $this->returnResponse('media not found', Response::HTTP_NOT_FOUND);
        }

        if($request->get('_route') !== 'admin_api_media_get'){
            if($media->getOwner() != $user){
                return $this->returnResponse("not user's media", Response::HTTP_FORBIDDEN);
            }
            if(!$media->isValid()){
                return $this->returnResponse('invalid media cannot be removed', Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS);
            }
        }

        $this->imageStorage->delete($media->getFile());
        if($this->imageStorage->fileExists($media->getThumb())){
            $this->imageStorage->delete($media->getThumb());
        }

        $this->mediaRepository->remove($media);

        return $this->returnResponse('deleted', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/editorial/medias/{id}/{attribute}", name="api_media_editorial_put", methods="PUT")
     * @Route("/api/medias/{id}/{attribute}", name="api_media_put", methods="PUT")
     * @Route("/admin/api/medias/{id}/{attribute}", options={"expose"=true}, name="admin_api_media_put", methods="PUT")
     * 
     * @OA\Put(
     *      tags={"Media"},
     *      path="/api/medias/{id}/{attribute}",
     *      security={"bearer"},
     *      description="Affecte value à l'attribut du media d'identifiant id (attibute dans ['folder', 'name'])",
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Parameter(
     *          name="attribute",
     *          in="path",
     *          description="Attribut à modifier : doit etre parmi ['folder', 'name']",
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="value",
     *                  type="string",
     *                  description="valeur à affecter à l'attribut [folder => peut être null]",
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Attributs de la carte créée",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="integer",
     *                  description="identifiant du média modifié",
     *              ),
     *              @OA\Property(
     *                  property="attribute",
     *                  type="string",
     *                  description="attribut modifié",
     *              ),
     *              @OA\Property(
     *                  property="value",
     *                  type="string",
     *                  description="nouvelle valeur affectée à l'attribut",
     *              ),
     *          )
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/BadRequest"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="403", ref="#/components/responses/Forbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     * )
     */
    public function put($id, $attribute, Request $request): Response
    {
        if($request->get('_route') == 'api_media_editorial_put'){
            if(!$this->isGranted('ROLE_EDITOR')){
                return $this->returnResponse('forbidden', Response::HTTP_FORBIDDEN);
            }
            $user = $this->userRepository->find($this->getParameter('user_editor_default_id'));
        }else{
            $user = $this->getUser();
        }

        /** @var Media $media */
        $media = $this->mediaRepository->find($id);

        if(!$media){
            return $this->returnResponse('media not found', Response::HTTP_NOT_FOUND);
        }

        if($request->get('_route') !== 'admin_api_media_put' and $media->getOwner() != $user){
            //un non-admin ne peut modifier que ses médias
            return $this->returnResponse("not user's media", Response::HTTP_FORBIDDEN);
        }

        $content = json_decode($request->getContent());
        if(!isset($content->value)){
            return $this->returnResponse('parameter "value" required', Response::HTTP_BAD_REQUEST);
        }
        $value = $content->value;

        switch($attribute){
            case 'valid':
                //seul un admin peut modifier la validité d'un média
                if($request->get('_route') !== 'admin_api_media_put'){
                    return $this->returnResponse('forbidden', Response::HTTP_FORBIDDEN);
                }
                $media->setValid($value);
                break;
            case 'folder':
                if($value){
                    $media->setFolder($value);
                }else{
                    $media->setFolder('Non classé');
                }
                break;
            case 'name':
                if(!$value){
                    return $this->returnResponse('value for name can\'t be empty nor null', Response::HTTP_BAD_REQUEST);
                }
                $media->setName($value);
                break;
            default:
                return $this->returnResponse('attribute not used', Response::HTTP_BAD_REQUEST);
        }

        $this->mediaRepository->persist($media);

        return new JsonResponse(array(
            'id' => $media->getId(),
            'attribute' => $attribute,
            'value' => $value
        ), Response::HTTP_OK);
    }

    /**
     * @Route("/editorial/medias/{id}/file", name="api_media_editorial_edit_file", methods="POST")
     * @Route("/api/medias/{id}/file", name="api_media_edit_file", methods="POST")
     * @Route("/admin/api/medias/{id}/file", name="admin_api_media_edit_file", methods="POST")
     * 
     * @OA\Post(
     *      tags={"Media"},
     *      path="/api/medias/{id}/file",
     *      security={"bearer"},
     *      description="Modifie l'image' associé au media",
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Parameter(
     *          name="file",
     *          in="formData",
     *          required=true,
     *          description="image à associer à la bibliothèque, le fichier doit faire moins de 2 Mo",
     *          @OA\Schema(type="file", format="png|jpg|jpeg|gif")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Attributs de la carte créée",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="integer",
     *                  description="identifiant du média modifié",
     *              ),
     *              @OA\Property(
     *                  property="attribute",
     *                  type="string",
     *                  description="attribut modifié",
     *              ),
     *              @OA\Property(
     *                  property="view_url",
     *                  type="string",
     *                  description="url de l'image",
     *              ),
     *              @OA\Property(
     *                  property="thumb_url",
     *                  type="string",
     *                  description="url de la vignette",
     *              ),
     *          ),
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/BadRequest"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="403", ref="#/components/responses/Forbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     *      @OA\Response(response="413", ref="#/components/responses/TooLarge"),
     * )
     */
    public function editFile($id, Request $request): Response
    {
        if($request->get('_route') == 'api_media_editorial_edit_file'){
            if(!$this->isGranted('ROLE_EDITOR')){
                return $this->returnResponse('forbidden', Response::HTTP_FORBIDDEN);
            }
            $user = $this->userRepository->find($this->getParameter('user_editor_default_id'));
        }else{
            $user = $this->getUser();
        }

        /** @var Media $media */
        $media = $this->mediaRepository->find($id);

        if(!$media){
            return $this->returnResponse('media not found', Response::HTTP_NOT_FOUND);
        }

        if($request->get('_route') !== 'admin_api_media_edit_file' and $media->getOwner() != $user){
            //un non-admin ne peut modifier que ses médias
            return $this->returnResponse('not user\'s media', Response::HTTP_FORBIDDEN);
        }

        $file = $this->checkFile($request->files->get('file'));
        if($file instanceof Response){
            //une erreur a été détectée dans la requete
            return $file;
        }

        if($request->get('_route') == 'api_media_editorial_put'){
            $checkSize = $this->checkSize($user, $file, $media->getSize());
            if($checkSize instanceof Response){
                return $checkSize;
            }
        }

        $this->imageStorage->delete($media->getFile());
        if($media->getThumb()){
            $this->imageStorage->delete($media->getThumb());
        }

        $extension = $file->guessClientExtension();
        $filename = $media->getFilename();

        $this->writeFiles($file, $filename.'.'.$extension);

        $media->setFile($filename.'.'.$extension);
        $media->setThumb('thumb_'.$filename.'.'.$extension);
        $media->setExtension($extension);
        $media->setSize(filesize($file));
        $this->mediaRepository->persist($media);

        return new JsonResponse(array(
            'id' => $media->getId(),
            'attribute' => 'file',
            'img' => $this->generateUrl('api_media_view_image', array("filename" => 'img_'.$media->getFilename()), UrlGeneratorInterface::ABSOLUTE_URL),
            'thumb' => $this->generateUrl('api_media_view_image', array("filename" => 'thumb_'.$media->getFilename()), UrlGeneratorInterface::ABSOLUTE_URL)
        ), Response::HTTP_OK);
    }

    private function checkFile($file){
        if(!$file){
            return $this->returnResponse('Parameter "file" is required', Response::HTTP_BAD_REQUEST);
        }

        if(!in_array($file->guessExtension(), array('jpg', 'jpeg', 'gif', 'png', 'svg'))){
            return $this->returnResponse("File must be an image in format 'jpg', 'jpeg', 'gif', 'png', 'svg'", Response::HTTP_BAD_REQUEST);
        }
        
        $filesize = filesize($file);
        if($filesize > $this->uploadMaxSize){
            return $this->returnResponse('File must be less than 2MB', Response::HTTP_BAD_REQUEST);
        }

        return $file;
    }

    private function checkSize($user, $newFile, $editedFileSize = 0){
        
        if($user->getMediasSize() + filesize($newFile) - $editedFileSize > $user->getMediasizeLimit()){
            return $this->returnResponse(
                "global size of user's medias must be lower than ". ceil($user->getMediasizeLimit()/1024/1024)." MB",
                 Response::HTTP_REQUEST_ENTITY_TOO_LARGE
            );
        }

        return true;
    }

    /**
     * @param Media $media
     * @param UploadedFile $file
     * @param string|null $fullname ex : aze123.jpg
     * @return void
     */
    private function writeFiles(UploadedFile $file, string $fullname){

        $this->imageStorage->write($fullname, $file->getContent());

        // création de la miniature
        if($file->guessExtension() == 'svg'){
            $this->imageStorage->write('thumb_'.$fullname, $file->getContent());
        }else{
            $this->createThumb($file, $fullname);
        }
    }

    private function createThumb(UploadedFile $file, string $fullname){
        $width = 200;
        $height = 200;
        list($width_orig, $height_orig) = getimagesize($file);

        //ne pas agrandir l'image
        if($width_orig <= $width && $height_orig <= $height){
            $this->imageStorage->write('thumb_'.$fullname, $file->getContent());
            return;
        }

        //calcul des dimensions de la vignette
        $ratio_orig = $width_orig/$height_orig;
        if ($width/$height > $ratio_orig) {
            $width = $height*$ratio_orig;
        } else {
            $height = $width/$ratio_orig;
        }

        // creation de l'image
        switch($file->guessExtension()){
            case 'png':
                $image = imagecreatefrompng($file);
                break;
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file);
                break;
            case 'gif':
                $image = imagecreatefromgif($file);
                break;
        }
        $image_p = imagecreatetruecolor($width, $height);

        $background = imagecolorallocate($image_p , 0, 0, 0);

        // removing the black from the placeholder
        imagecolortransparent($image_p, $background);

        // turning off alpha blending (to ensure alpha channel information
        // is preserved, rather than removed (blending with the rest of the
        // image in the form of black))
        imagealphablending($image_p, false);

        // turning on alpha channel information saving (to ensure the full range
        // of transparency is preserved)
        imagesavealpha($image_p, true);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        $fullPath = $this->getParameter('kernel.project_dir')
            .DIRECTORY_SEPARATOR.'var'
            .DIRECTORY_SEPARATOR.'image.'.$file->guessExtension() ;

        switch($file->guessExtension()){
            case 'png':
                imagepng($image_p, $fullPath);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($image_p, $fullPath);
                break;
            case 'gif':
                imagegif($image_p, $fullPath);
                break;
        }
        $this->imageStorage->write('thumb_'.$fullname,file_get_contents($fullPath) );
        unlink($fullPath);
    }

    private function getSubDir(Media $media){
        return floor($media->getId() / 1000);
    }
}
