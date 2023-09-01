<?php

namespace App\Serializer\Normalizer;

use App\Entity\Media;
use OpenApi\Annotations as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;


/**
 * @OA\Schema(
 *      schema="Media",
 *      description="media",
 *      @OA\Property(
 *          property="id",
 *          type="integer",
 *          description="identifiant du media",
 *      ),
 *      @OA\Property(
 *          property="name",
 *          type="string",
 *          description="nom de l'image originale",
 *      ),
 *      @OA\Property(
 *          property="file",
 *          type="string",
 *          description="nom du fichier enregistré",
 *      ),
 *      @OA\Property(
 *          property="owner",
 *          type="string",
 *          description="username de l'utilisateur qui a chargé le media",
 *      ),
 *      @OA\Property(
 *          property="uploaded_at",
 *          type="string",
 *          format="date-time",
 *          description="date du chargement de l'image",
 *      ),
 *      @OA\Property(
 *          property="size",
 *          type="integer",
 *          description="Taille de l'image (en octet)",
 *      ),
 *      @OA\Property(
 *          property="folder",
 *          type="string",
 *          description="Nom du fichier contenant le media",
 *      ),
 *      @OA\Property(
 *          property="valid",
 *          type="boolean",
 *          description="Validité de l'image",
 *      ),
 *      @OA\Property(
 *          property="view_url",
 *          type="string",
 *          description="Url de l'image",
 *          format="uri",
 *      ),
 *      @OA\Property(
 *          property="thumb_url",
 *          type="string",
 *          description="Url de la vignette de l'image",
 *          format="uri",
 *      ),
 * )
 * 
 * @OA\Schema(
 *      schema="Media_list",
 *      description="Liste de médias",
 *      @OA\Property(property="medias", type="array", @OA\Items(ref="#/components/schemas/Media")),
 *      @OA\Property(property="count", type="integer", description="Nombre de cartes correspondant à la recherche"),
 *      @OA\Property(property="limit", type="integer", description="Nombre de cartes recues dans la requete"),
 *      @OA\Property(property="offset", type="integer", description="Nombre de cartes à passer avant de ls inclure dans la requete"),
 * )
 */
class MediaNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;
    private $router;

    public function __construct(ObjectNormalizer $normalizer, ContainerInterface $container)
    {
        $this->normalizer = $normalizer;
        $this->router = $container->get('router');
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        if($object instanceof Media){
            return $this->serializeMedia($object);
        }


        unset($object['normalizer']);

        $medias = [];

        /** @var Media $media */
        foreach($object['medias'] as $media){
            $medias[] = $this->serializeMedia($media);
        }

        $result = array(
            "medias" => $medias,
            "count" => $object['count'],
            "limit" => $object['limit'],
            "offset" => $object['offset'],
        );

        return $result;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        if( is_array($data) and isset($data['normalizer']) and $data['normalizer'] == 'media' ){
            //on est dans le cas d'une liste de medias
            return true;
        }
        return $data instanceof Media;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    private function serializeMedia($media){
        /** @var Media $media */
        $array = [];
        $array['id'] = $media->getId(); 
        $array['valid'] = $media->isValid(); 
        $array['name'] = $media->getName(); 
        $array['uploaded_at'] = $media->getUploadedAt()->format(\DateTimeInterface::W3C); 
        $array['size'] = $media->getSize(); 
        // $array['file'] = $media->getFile(); 
        $array['folder'] = $media->getFolder(); 
        $array['owner'] = $media->getOwner()->getUsername();
        $array['view_url'] =  preg_replace(
            '/^http:/',
            'https:', 
            $this->router->generate('api_media_view_image', array("filename" => 'img_'.$media->getFilename()), UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $array['thumb_url'] =  preg_replace(
            '/^http:/',
            'https:', 
            $this->router->generate('api_media_view_image', array("filename" => 'thumb_'.$media->getFilename()), UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return  $array;
    }
}
