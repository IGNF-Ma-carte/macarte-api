<?php

namespace App\Controller\Api;

use DateTime;
use stdClass;
use Exception;
use App\Entity\Map;
use App\Entity\User;
use App\Entity\Theme;
use App\Entity\DataMap;
use App\Service\RandomService;
use OpenApi\Annotations as OA;
use App\Entity\MapViewTracking;
use App\Repository\MapRepository;
use App\Repository\UserRepository;
use App\Repository\ThemeRepository;
use App\Repository\DataMapRepository;
use League\Flysystem\FilesystemOperator;
use Doctrine\Persistence\ManagerRegistry;
use App\Controller\Api\ApiAbstractController;
use App\Repository\MapViewTrackingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class ApiMapController extends ApiAbstractController
{
    private $mapRepository;
    private $themeRepository;
    private $dataMapRepository;
    private $userRepository;
    private $trackingRepository;
    private $randomService;
    private $mapStorage;
    
    public function __construct(
        MapRepository $mapRepository, 
        ThemeRepository $themeRepository, 
        DataMapRepository $dataMapRepository,
        UserRepository $userRepository,
        MapViewTrackingRepository $trackingRepository,
        RandomService $randomService,
        FilesystemOperator $mapStorage
    )
    {
        $this->mapRepository = $mapRepository;
        $this->themeRepository = $themeRepository;
        $this->dataMapRepository = $dataMapRepository;
        $this->userRepository = $userRepository;
        $this->trackingRepository = $trackingRepository;
        $this->randomService = $randomService;
        $this->mapStorage = $mapStorage;
    }

    /**
     * @Route("/api/maps", name="api_map_get", methods={"GET"}))
     * @Route("/admin/api/maps", name="admin_api_map_get", methods={"GET"}, options={"expose"=true}))
     * 
     * @OA\Get(
     *      tags={"Map"},
     *      path="/api/maps",
     *      @OA\Parameter(
     *          name="context",
     *          in="query",
     *          description="Contexte de la recherche. Doit être parmi :<br>
                    - 'atlas' : recherche publique parmi les cartes partagées <br>
                    - 'profile' : recherche parmi les carte de l'utilisateur. Il doit être connecté (erreur 401 possible), <br>
                    <br>
                    Une carte partagée est une carte <br>
                    - ayant un thème <br>
                    - avec les attribut share = 'atlas', valid = true et active = true  <br>
                    <br>
                    **Ajouter le header 'Authorization: Bearer xxx' pour le cas 'profile'** pour ne pas avoir les erreurs 401 et 403
     *          ",
     *          required=false,
     *          @OA\Schema(type="string", default="atlas")
     *      ),
     *      @OA\Parameter(
     *          name="query",
     *          in="query",
     *          description="Un ou plusieurs mots que doivent contenir le titre, la description ou le thème <br>
                    S'il y a plusieurs mots, la recherche l'interprètera comme un ET 
                ",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="theme",
     *          in="query",
     *          description="Thème des cartes <br>
                    - nom du thème  <br>
                    - 'undefined' pour avoir les cartes qui n'ont pas de thème <br>
                    - null pour avoir toutes les cartes, indépendemment du thème
                ",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="type",
     *          in="query",
     *          description="Type des cartes ('macarte', 'storymap')",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="premium",
     *          in="query",
     *          description="Premium des cartes ('default', 'edugeo')",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="user",
     *          in="query",
     *          description="Nom public du créateur des cartes",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="active",
     *          in="query",
     *          description="Si context 'profile' et si renseigné, n'inclut que les cartes actives (true) ou non-actives (false)<br>
                        Si context 'atlas', active = true
                ",
     *          required=false,
     *          @OA\Schema(type="boolean")
     *      ),
     *      @OA\Parameter(
     *          name="valid",
     *          in="query",
     *          description="Si context 'profile' et si renseigné, n'inclut que les cartes valides (true) ou invalides (false)<br>
                        Si context 'atlas', valid = true
                ",
     *          required=false,
     *          @OA\Schema(type="boolean")
     *      ),
     *      @OA\Parameter(
     *          name="share",
     *          in="query",
     *          description="Si context 'profile' et si renseigné, n'inclut que les cartes publiques ('atlas') ou privées ('private') de l'utilisateur<br>
                    Si context 'atlas', share = 'atlas' 
                ",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Tri des cartes ('date', 'rank', 'views')",
     *          required=false,
     *          @OA\Schema(type="string", default="date")
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Nombre maximum de cartes à retourner<br>
                    - Limit peut être 'all' dans le context 'profile' -> l'utilisateur reçoit toutes ses cartes<br>
                    - Si limit > 100, limit sera considérée comme 100
                ",
     *          required=false,
     *          @OA\Schema(type="integer|string", default=25),
     *      ),
     *      @OA\Parameter(ref="#/components/parameters/offset"),
     * 
     *      @OA\Response(
     *          response="200",
     *          description="Liste des cartes (tout ou partie), recherchées selon les critères définis dans le query",
     *          @OA\JsonContent(ref="#/components/schemas/Map_research")
     *      ),
     *      @OA\Response(response="206", ref="#/components/responses/PartialContent"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="403", ref="#/components/responses/Forbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")
     * )
     */
    public function research(Request $request, SerializerInterface $serializer): Response
    {
        if($request->get('_route') == 'admin_api_map_get'){
            $context = 'admin';
        }else{
            $context = $this->treatContext($request);
        }

        switch($context){
            case 'atlas':
                $mustHaveTheme = true;
                break;
            case 'profile':
                if(!$this->getUser()){
                    return $this->returnResponse('Not connected', Response::HTTP_UNAUTHORIZED);
                }
                $mustHaveTheme = false;
                break;
            case 'admin':
                if(!$this->getUser()){
                    return $this->returnResponse('Not connected', Response::HTTP_UNAUTHORIZED);
                }
                if(!$this->isGranted('ROLE_SUPER_ADMIN')){
                    return $this->returnResponse('Forbidden', Response::HTTP_FORBIDDEN);
                }
                $mustHaveTheme = false;
                break;
        }// fin switch($context)

        $userPublicName = $this->treatUser($request, $context);
        if( $userPublicName instanceof Response){
            //user not found
            return $userPublicName;
        }
        $themeName = $this->treatTheme($request);
        if( $themeName instanceof Response){
            // theme not found
            return $themeName;
        }
        $type = $this->treatType($request);
        $premium = $this->treatPremium($request);
        $sort = $this->treatSort($request);
        $active = $this->treatActive($request, $context);
        $share = $this->treatShare($request, $context);
        $valid = $this->treatValid($request, $context);
        $limit = $this->treatLimit($request, $context);
        $query = $this->treatQuery($request);
        $offset = $this->treatOffset($request);

        /** @var Array $result */
        $result = $this->mapRepository->searchFullText(
            $query, $userPublicName, $themeName, $type, $premium, 
            $active, $valid,
            $sort, $offset, $limit, 
            $mustHaveTheme, $share
        );
        $result['normalizer'] = 'research';

        $nbResult = $result['count'];

        $json = $serializer->serialize($result, 'json', array('context' => $context));
        
        if($nbResult <= $limit){
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
            'Content-Range' => $offset.'-'.($offset + $limit - 1) .'/'.$nbResult,
            'Link' => $linkHeader,
            'Content-Type' => 'application/json',
        ));

        return $response;
    }

    /**
     * @Route("/api/maps/users", name="api_maps_user_autocomplete")
     * @Route("/admin/api/maps/users", name="admin_api_maps_user_autocomplete")
     * 
     * @OA\Get(
     *      tags={"Map"},
     *      path="/api/maps/users",
     *      description="Autocompletion des utilisateurs ayant des cartes publiques correspondant aux critères de recherche",
     *      @OA\Parameter(
     *          name="theme",
     *          in="query",
     *          description="Thème des cartes (nom du thème ou 'Non défini')",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="public_name",
     *          in="query",
     *          description="string que le public_name des utilisateurs doit inclure",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="query",
     *          in="query",
     *          description="Un ou plusieurs mots que doivent contenir le titre, la description ou le thème <br>
                    S'il y a plusieurs mots, la recherche l'interprètera comme un ET 
                ",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(ref="#/components/parameters/limit"),
     *      @OA\Response(
     *          response="200",
     *          description="Liste des noms publics des utilisateurs correspondant aux critères de recherche",
     *          @OA\JsonContent(
     *              type="array", 
     *              @OA\Items(type="string"),
     *          )
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")
     * )
     */
    public function autocompleteUsers(Request $request, ManagerRegistry $doctrine): Response
    {
        $query = $this->treatQuery($request);
        $username = $request->get('public_name');
        $themeName = $this->treatTheme($request);
        if( $themeName instanceof Response){
            // theme not found
            return $themeName;
        }
        $limit = intval($request->get('limit')) ?: 15;

        $result = $this->mapRepository->searchFullTextUser($query, $username, $themeName, $limit);

        return new JsonResponse($result);
    }
    /**
     * @Route("/api/maps/{id}", name="api_map_get_view", methods={"GET"})
     * @Route("/api/maps/{id}/edit", name="api_map_get_edit", methods={"GET"})
     * @Route("/admin/api/maps/{id}", name="admin_api_map_view", methods={"GET"}, options={"expose"=true})
     * 
     * @OA\Get(
     *      tags={"Map"},
     *      path="/api/maps/{view_id}",
     *      description="Récupérer les metadata de la carte par son identifiant de visualisation",
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Response(
     *          response="200",
     *          description="Attributs d'une carte pour visualisation",
     *          @OA\JsonContent(ref="#/components/schemas/Map_view")
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     *      @OA\Response(response="451", ref="#/components/responses/Invalid")
     * )
     * 
     * @OA\Get(
     *      tags={"Map"},
     *      path="/api/maps/{edit_id}/edit",
     *      security={"bearer"},
     *      description="Récupérer les metadata de la carte par son identifiant d'édition'",
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Response(
     *          response="200",
     *          description="Attributs d'une carte pour edition",
     *          @OA\JsonContent(ref="#/components/schemas/Map_view")
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     *      @OA\Response(response="451", ref="#/components/responses/Invalid")
     * )
     */
    public function view(string $id, SerializerInterface $serializer, Request $request): Response
    {
        $attr = 'idView';
        if($request->get('_route') == 'api_map_get_edit'){
            if(!$this->getUser()){
                //une carte ne peut etre modifiée que par un user connecté
                return $this->returnResponse('Not connected', Response::HTTP_UNAUTHORIZED);
            }
            $attr = 'idEdit';
        }

        /** @var Map $map */
        $map = $this->mapRepository->findOneBy(array($attr => $id));

        if(!$map){
            return $this->returnResponse('Map not found', Response::HTTP_NOT_FOUND);
        }

        if(
            $map->getCreator() != $this->getUser()
            and $request->get('_route') == 'api_map_get_view'
        ){
            if(!$map->isActive()){
                //la carte est en mode brouillon, on ne doit pas savoir qu'elle existe
                return $this->returnResponse('Map not found', Response::HTTP_NOT_FOUND);
            }
            if(!$map->isValid()){
                //451 carte non conforme
                return $this->returnResponse('Map invalid', Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS);
            }
        }

        if($request->get('_route') == 'api_map_get_view'){
            $json = $serializer->serialize($map, 'json', array('context' => 'view', 'user' => $this->getUser()));
        }else{
            $json = $serializer->serialize($map, 'json', array('context' => 'edit', 'user' => $this->getUser()));
        }

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/api/maps/{id}/file", name="api_map_get_view_data", methods={"GET"})
     * @Route("/api/maps/{id}/edit/file", name="api_map_get_edit_data", methods={"GET"})
     * 
     * @OA\Get(
     *      tags={"Map"},
     *      path="/api/maps/{view_id}/file",
     *      description="Récupérer les data de la carte par son identifiant de visualisation",
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Response(
     *          response="200",
     *          description="Contenu d'une carte",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     *      @OA\Response(response="451", ref="#/components/responses/Invalid"),
     * )
     * 
     * @OA\Get(
     *      tags={"Map"},
     *      security={"bearer"},
     *      path="/api/maps/{edit_id}/edit/file",
     *      description="Récupérer les data de la carte par son identifiant d'édition",
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Response(
     *          response="200",
     *          description="Contenu d'une carte",
     *          @OA\JsonContent()
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     *      @OA\Response(response="451", ref="#/components/responses/Invalid")
     * )
     */
    public function getFile(string $id, Request $request): Response
    {
        $attr = 'idView';
        if($request->get('_route') == 'api_map_get_edit_data'){
            if(!$this->getUser()){
                //une carte ne peut etre modifiée que par un user connecté
                return $this->returnResponse('Not connected', Response::HTTP_UNAUTHORIZED);
            }
            $attr = 'idEdit';
        }

        /** @var Map $map */
        $map = $this->mapRepository->findOneBy(array($attr => $id));

        if(!$map){
            return $this->returnResponse('Map not found', Response::HTTP_NOT_FOUND);
        }

        /** @todo ajouter vérif utilisateur (propriétaire ou admin) et le statut de publication share */
        if($request->get('_route') == 'api_map_get_view_data' and !$map->isValid()){
            //451 Unavailable For Legal Reasons
            return $this->returnResponse('Map invalid', Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS);
        }

        // vérification si on incrémente le nb de vues
        if($attr == 'idView'){
            $ip = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
            $today = new DateTime();

            // 1-supprimer tous les trackings d'avant la date courante
            $this->trackingRepository->removeBefore($today);
            
            // 2-rechercher le traking (ip + map) du jour courant
            $track = $this->trackingRepository->findOneBy( ['mapId' => $map->getId(), 'ip' => $ip]);

            if(!$track){
                //3-si pas de tracking : nbView +1 et ajout tracking
                $map->setNbView($map->getNbView() + 1);
                $this->mapRepository->persist($map, true);

                $track = (new MapViewTracking)
                    ->setMapId($map->getId())
                    ->setIp($ip)
                    ->setDate($today)
                ;
                $this->trackingRepository->persist($track, true);
            }
        }

        // tranformation ancienne structure (données dans la base)
        // à la nouvelle structure (données dans un fichier)
        if(!$map->getDataFile()){
            $data = (object) $map->getDataMap()->getData();
            $this->updateMapData($map, $data);
        }
        $data = $this->mapStorage->read($map->getDataFile());

        return new Response($data, Response::HTTP_OK);
    }
    
    /**
     * @Route("/api/maps", name="api_map_create", methods={"POST"})
     * 
     * @OA\Post(
     *      tags={"Map"},
     *      path="/api/maps",
     *      security={"bearer"},
     *      @OA\Parameter(
     *          name="carte",
     *          in="formData",
     *          required=true,
     *          @OA\Schema(ref="#/components/schemas/Map_add")
     *      ),
     *      @OA\Parameter(
     *          name="file",
     *          in="formData",
     *          required=true,
     *          description="Contenu de la carte au format .carte",
     *          @OA\Schema(type="file", format="json")
     *      ),
     *      @OA\Response(
     *          response="201",
     *          description="Attributs de la carte créée",
     *          @OA\JsonContent(ref="#/components/schemas/Map_view")
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/BadRequest"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     * )
     */
    public function create(Request $request, SerializerInterface $serializer): Response
    {
        if($this->getUser()->hasRole('ROLE_EDUGEO_ELEVE')){
            return $this->returnResponse('Les élèves ne sont pas autorisés à enregistrer des cartes', Response::HTTP_FORBIDDEN);
        }

        $carte = $this->checkMapParameters( json_decode($request->get('carte')) );
        if($carte instanceof Response){
            //une erreur a été détectée dans la requete
            return $carte;
        }

        $data = $this->checkFile($request->files->get('file'));
        if( $data instanceof Response){
            //erreur dans le fichier joint
            return $data;
        }

        $map = $this->updateMap(new Map(), $carte, $data);

        $json = $serializer->serialize($map, 'json', array('context' => 'edit'));

        return new Response($json, Response::HTTP_CREATED, array(
            'Content-Type' => 'application/json',
        ));
    }

    
    /**
     * @Route("/api/maps/{id}", name="api_map_edit", methods={"PATCH"})
     * 
     * @OA\Patch(
     *      tags={"Map"},
     *      path="/api/maps/{edit_id}",
     *      security={"bearer"},
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Attributs de la carte, ne renseigner que les champs à modifier<br>
__new_edit_id__ : boolean, si true -> génère un nouvel identifiant de modifiant pour la carte
",
     *          @OA\JsonContent(
     *              allOf={ @OA\Schema(ref="#/components/schemas/Map_add") },
     *              @OA\Property(
     *                  property="new_edit_id",
     *                  type="boolean",
     *                  description="Demande à modifier l'id de modification",
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Attributs d'une carte pour visualisation",
     *          @OA\JsonContent(ref="#/components/schemas/Map_view")
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/BadRequest"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="403", ref="#/components/responses/Forbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound"),
     * )
     */
    public function edit(string $id, Request $request, SerializerInterface $serializer): Response
    {
        /** @var Map $map */
        $map = $this->mapRepository->findOneBy(array('idEdit' => $id));

        if(!$map){
            return $this->returnResponse('Map not found', Response::HTTP_NOT_FOUND);
        }

        // on met à jour les attributs de $map avec les nouvelles valeurs des attributs envoyées
        $newAttrs = json_decode($request->getContent());
        $attrs = json_decode($serializer->serialize($map, 'json', array('context' => 'edit')));
        foreach($newAttrs as $key => $value){
            $attrs->$key = $value;
        } 
        if(isset($newAttrs->edit_id) and $newAttrs->edit_id){
            if($map->getCreator() != $this->getUser()){
                return $this->returnResponse('only the creator can request new_id_edit', Response::HTTP_FORBIDDEN);
            }
            $attrs->edit_id = true;
        }
        $carte = $this->checkMapParameters($attrs);

        if($carte instanceof Response){
            //une erreur a été détectée dans la requete
            return $carte;
        }

        $map = $this->updateMap($map, $carte);

        $json = $serializer->serialize($map, 'json', array('context' => 'edit'));

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/admin/api/maps/{id}/{attribute}", name="admin_api_map_put", options={"expose"=true}, methods={"PUT"})
     */
    public function editAttribute($id, $attribute, Request $request, ManagerRegistry $doctrine ): Response
    {
        /** @var Map $map */
        $map = $this->mapRepository->findOneBy(array('idEdit' => $id));
        if(!$map){
            return $this->returnResponse('map not found', Response::HTTP_NOT_FOUND);
        }

        $content = json_decode($request->getContent());
        $value = $content->value;

        switch($attribute){
            case 'title':
                if(!$value){
                    return $this->returnResponse('title is required', Response::HTTP_BAD_REQUEST);
                }
                $map->setTitle($value);
                break;
            case 'description':
                $map->setDescription($value);
                break;
            case 'img_url':
                if($value and !filter_var($value, FILTER_VALIDATE_URL)) {
                    return $this->returnResponse('invalid img_url value', Response::HTTP_BAD_REQUEST);
                }
                $map->setImgUrl($value);
                break;
            case 'theme':
                /** @var Theme $theme */
                $theme = $doctrine->getRepository(Theme::class)->find($value);
                if(!$theme){
                    return $this->returnResponse('theme not found', Response::HTTP_NOT_FOUND);
                }
                $map->setTheme($theme);
                $value = $theme->getName();
                break;
            case 'valid':
                $value = $value == "true" ? true : false;
                if($value){
                    $map->setInvalidatedAt(null);
                }else{
                    $map->setInvalidatedAt(new \DateTime());
                }
                $map->setValid($value);
                break;
            case 'share':
                if($value == Map::SHARE_ATLAS or $value == Map::SHARE_PRIVATE){
                    $map->setShare($value);
                }else{
                    return $this->returnResponse('value must be in ['. implode(', ', Map::getShares()) . ']', Response::HTTP_BAD_REQUEST);
                }
                break;
            case 'new_id_edit':
                $value = $this->generateIdUnique('edit');
                $map->setIdEdit($value);
                break;
            default:
                return $this->returnResponse('attribute not used', Response::HTTP_BAD_REQUEST);
        }

        $map->setUpdatedAt(new \DateTime());
        $map->setEditor($this->getUser());
        $this->mapRepository->persist($map);

        return new JsonResponse(
            array(
                'id' => $map->getId(),
                'attribute' => $attribute,
                'value' => $value,
            ), 
            Response::HTTP_OK,
            array(
                'Content-Type' => 'application/json',
            )
        );
    }

    /**
     * @Route("/api/maps/{id}/file", name="api_maps_data_edit", methods={"POST"})
     * 
     * @OA\Post(
     *      tags={"Map"},
     *      path="/api/maps/{edit_id}/file",
     *      security={"bearer"},
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Parameter(
     *          name="file",
     *          in="formData",
     *          required=true,
     *          description="Contenu de la carte au format .carte",
     *          @OA\Schema(type="file", format="json")
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Contenu d'une carte"
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")      
     * )
     */
    public function editData(string $id, Request $request): Response
    {
        /** @var Map $map */
        $map = $this->mapRepository->findOneByIdEdit($id);

        if(!$map){
            return $this->returnResponse('Map not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->checkFile($request->files->get('file'));
        if( $data instanceof Response){
            //erreur dans le fichier joint
            return $data;
        }

        $map = $this->updateMapData($map, $data);
        $map->setUpdatedAt(new \DateTime());
        $map->setEditor($this->getUser());
        $this->mapRepository->persist($map);

        return $this->returnResponse('Data has been updated', Response::HTTP_OK);
    }

    /**
     * @Route("/api/maps/{id}", name="api_map_delete", methods={"DELETE"})
     * 
     * @OA\Delete(
     *      tags={"Map"},
     *      path="/api/maps/{edit_id}",
     *      security={"bearer"},
     *      @OA\Parameter(ref="#/components/parameters/id"),
     *      @OA\Response(response="204", ref="#/components/responses/Deleted"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     *      @OA\Response(response="403", ref="#/components/responses/Forbidden"),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")
     * )
     */
    public function delete(string $id, Request $request): Response
    {
        /** @var Map $map */
        $map = $this->mapRepository->findOneBy(array('idEdit' => $id));

        if(!$map){
            return $this->returnResponse('Map not found', Response::HTTP_NOT_FOUND);
        }

        if($map->getCreator() != $this->getUser()){
            return $this->returnResponse('Only the creator car delete a map', Response::HTTP_FORBIDDEN);
        }

        if($map->getDataFile()){
            $this->mapStorage->delete($map->getDataFile());
        }
        $this->mapRepository->remove($map);

        return $this->returnResponse('Map deleted', Response::HTTP_NO_CONTENT);
    }


    /* ***********************************
     * Traitement des paramètres de la recherche
     ***************************************/

    private function treatContext($request){
        $context = $request->get('context');
        if(!$context OR !in_array( $context, array('atlas', 'profile')) ){
            $context = 'atlas';
        }
        return $context;
    }

    private function treatActive($request, $context){
        if($context == 'atlas'){
            return true;
        }
        // filter_var(null, FILTER_VALIDATE_BOOLEAN) -> false !!!
        if($request->get('active') === null or $request->get('active') === ""){
            return null;
        }
        return filter_var( $request->get('active'), FILTER_VALIDATE_BOOLEAN);
    }

    private function treatShare($request, $context){
        if($context == 'atlas'){
            return 'atlas';
        }
        if($request->get('share') == 'private' or $request->get('share') == 'atlas') {
            return $request->get('share');
        }
        
        return null;
    }
    private function treatValid($request, $context){
        if($context == 'atlas'){
            return true;
        }
        // filter_var(null, FILTER_VALIDATE_BOOLEAN) -> false !!!
        if($request->get('valid') === null or $request->get('valid') === ""){
            return null;
        }
        return filter_var( $request->get('valid'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param Request $request
     * met en forme $query pour compatible avec la recherche
     *  - supprime les espaces multiples et aux extrémités
     *  - remplace les espaces par &, un ET dans la recherche
     */
    private function treatQuery($request){
        $query = $request->get('query');
        //supprimer les espaces au début et à la fin
        $query = trim($query);
        //remplacer les espaces multiples au milieu par un unique espace
        $query = preg_replace('/\s\s+/', ' ', $query);
        //remplacer l'espace par un & pour la fonction de recherche
        $query = str_replace(' ', '&' ,$query);

        return $query;
    }
     
    /**
     * @return string|Response
     */
    private function treatTheme($request){
        switch($request->get('theme')){
            case null : 
                return null;
            case 'undefined': 
                return 'undefined';
            default:
                $themeName = $request->get('theme');

                if(! $this->themeRepository->findOneBy(array('name' => $themeName))){
                    return $this->returnResponse('Theme '.$themeName.' not found', Response::HTTP_NOT_FOUND);
                }

                return $themeName;
        }
    }
    
    private function treatType($request){
        switch($request->get('type')){
            case 'storymap':
                return 'storymap';
                break;
            case 'macarte' :
                return 'map';
            default:
                return null;
        }
    }
    
    private function treatPremium($request){
        switch($request->get('premium')){
            case null:
                return null;
            case 'Non défini':
                return 'not-defined';
            default:
                return $request->get('premium');
        }
    }

    /**
     * @return string|Response
     */
    private function treatUser($request, $context){
        if($context == 'profile'){
            return $this->getUser()->getPublicName();
        }
        $userPublicName = $request->get('user') ?: null;

        if($userPublicName and !$this->userRepository->findOneByPublicName($userPublicName)){
            return $this->returnResponse('User not Found', Response::HTTP_NOT_FOUND);
        }

        return $userPublicName;
    }

    private function treatSort($request){
        $sort = $request->get('sort');
        if(!$sort OR !in_array( $sort, array('date', 'rank', 'views')) ){
            $sort = 'date';
        }

        return $sort;
    }

    private function treatLimit($request, $context){
        $limitRequest = $request->get('limit');

        if($context == 'profile' and $limitRequest == 'all'){
            return null;
        }
        
        $limit = intval($limitRequest);

        if(!$limit){
            return 25;
        }

        if($context != 'profile' and $limit > 100 ){
            return 100;
        }
        return $limit;
    }

    private function treatOffset($request){
        return intval( $request->get('offset'));
    }

    /* ***********************************
     * Traitement du payload pour création/edition de la carte
     ***************************************/

    /**
     * @param Request $request
     * @return stdClass|Response
     */
    private function checkMapParameters(stdClass $data){
        
        if(!$data){
            return $this->returnResponse('Request body not valid', Response::HTTP_BAD_REQUEST);
        }

        if(!isset($data->theme_id) or !intval($data->theme_id) ){
            return $this->returnResponse('Parameter "theme_id" is required and be an integer > 0', Response::HTTP_BAD_REQUEST);
        }
        
        $theme = $this->themeRepository->find($data->theme_id);
        if(!$theme){
            return $this->returnResponse('Theme not found', Response::HTTP_NOT_FOUND);
        }
        $data->theme = $theme;

        if(!isset($data->title) or $data->title === null or $data->title === ""){
            return $this->returnResponse('Parameter "title" is required and must not be null nor empty', Response::HTTP_BAD_REQUEST);
        }

        if(!isset($data->type) or !in_array( $data->type, Map::getTypes()) ){
            return $this->returnResponse('Parameter "type" is required and must be in ["'.implode('", "', Map::getTypes() ).'"]', Response::HTTP_BAD_REQUEST);
        }
        
        if(!isset($data->premium) or !in_array( $data->premium, Map::getPremiums()) ){
            return $this->returnResponse('Parameter "premium" is required and must be in ["'.implode('", "', Map::getPremiums() ).'"]', Response::HTTP_BAD_REQUEST);
        }

        $bbox = $data->bbox;
        foreach ($bbox as $key => $value) {
            if(gettype($value) != 'double' and gettype($value) != 'integer'){
                unset($bbox[$key]);
            }
        }
        if(!$bbox or sizeof($bbox) != 4){
            return $this->returnResponse('Parameter "bbox" is required and must be size of 4 floats or integers', Response::HTTP_BAD_REQUEST);
        }

        if(!isset($data->share) OR !in_array( $data->share, Map::getShares()) ){
            $data->share = Map::SHARE_PRIVATE;
        }

        return $data;
    }

    private function checkFile($file){
        if(!$file){
            return $this->returnResponse('Parameter "file" is required and must contain map data in json format"', Response::HTTP_BAD_REQUEST);
        }

        try{
            $dataText = file_get_contents($file);
            $data = json_decode($dataText);
            if($data === null){
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $error = ' - No errors';
                    break;
                    case JSON_ERROR_DEPTH:
                        $error = ' - Maximum stack depth exceeded';
                    break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $error = ' - Underflow or the modes mismatch';
                    break;
                    case JSON_ERROR_CTRL_CHAR:
                        $error = ' - Unexpected control character found';
                    break;
                    case JSON_ERROR_SYNTAX:
                        $error = ' - Syntax error, malformed JSON';
                    break;
                    case JSON_ERROR_UTF8:
                        $error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                    default:
                        $error = ' - Unknown error';
                    break;
                }
                return $this->returnResponse('json error '.$error, Response::HTTP_BAD_REQUEST);
            }
        }catch(Exception $e){
            return $this->returnResponse('error : '. $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        return $data;
    }

    /**
     * @param Map $map
     * @param stdClass $carte
     * @param stdClass $data : données au format json
     * 
     * @return Map
     */
    private function updateMap(Map $map, $carte, $data = null):Map 
    {
        /** @var User $user */
        $user = $this->getUser();

        $map
            ->setTitle(strip_tags($carte->title))
            ->setDescription(isset($carte->description) ? strip_tags($carte->description) : "")
            ->setTheme($carte->theme)
            ->setType($carte->type)
            ->setShare($carte->share)
            ->setPremium($carte->premium)
            ->setLonMax($carte->bbox[0])
            ->setLatMax($carte->bbox[1])
            ->setLonMin($carte->bbox[2])
            ->setLatMin($carte->bbox[3])
            ->setImgUrl(isset($carte->img_url) ? strip_tags($carte->img_url) : "")
            ->setUpdatedAt(new DateTime())
            ->setEditor($user)
            ->setActive($carte->active)
        ;

        if(!$map->getId()){
            $map
                ->setCreator($user)
                ->setCreatedAt(new DateTime())
            ;

            $map->setIdView($this->generateIdUnique('view'));
            $map->setIdEdit($this->generateIdUnique('edit'));
        }

        if(isset($carte->new_edit_id) and $carte->new_edit_id){
            $map->setIdEdit($this->generateIdUnique('edit'));
        }

        $this->mapRepository->persist($map);

        if($data){
            $map = $this->updateMapData($map, $data);
        }

        return $map;
    }

    private function generateIdUnique($type){

        $attribute = 'id'.ucwords($type);
        $length = 12;
        if($attribute == 'idView'){
            $length = 6;
        }

        $string = '';
        do{
            $string = $this->randomService->getRandomString($length);
        } while( $this->mapRepository->findOneBy( array($attribute => $string)) );

        return $string;
    }

    /**
     * @param Map $map
     * @param stdClass $data : contenu de la carte 
     * 
     * @return Map $map
     */
    private function updateMapData(Map $map, stdClass $data){

        if($map->getDataMap()){
            // tranformation ancienne structure (données dans la base)
            // à la nouvelle structure (données dans un fichier)
            $dataMapId = $map->getDataMap()->getId();
            $dataMap = $this->dataMapRepository->find($dataMapId);
            $map->setDataMap(null);
            $this->mapRepository->persist($map);
            $this->dataMapRepository->remove($dataMap);
        }

        $path = $map->getId().'.json';
        $this->mapStorage->write($path, json_encode($data));

        $filesize = $this->mapStorage->fileSize($path);
        $map->setDataFile($path);
        $map->setFilesize($filesize);
        $this->mapRepository->persist($map);

        return $map;
    }

    private function getSubDir(Map $map){
        return floor($map->getId() / 1000);
    }
}
