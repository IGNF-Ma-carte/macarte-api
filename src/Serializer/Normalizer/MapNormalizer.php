<?php

namespace App\Serializer\Normalizer;

use App\Entity\Map;
use OpenApi\Annotations as OA;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;

/**
 * @OA\Schema(
 *      schema="Bbox",
 *      description="Emprise visible de la carte (Haut, droite, bas, gauche)",
 *      type="array", 
 *      @OA\Items(type="number"),
 *      minItems=4,
 *      maxItems=4
 * )
 * 
 * @OA\Schema(
 *      schema="Map_add",
 *      description="Carte",
 *      @OA\Property(
 *          property="title", 
 *          type="string", 
 *          description="Titre de la carte",
 *      ),
 *      @OA\Property(
 *          property="description", 
 *          type="string|null", 
 *          description="Description de la carte"
 *      ),
 *      @OA\Property(
 *          property="theme_id", 
 *          type="integer", 
 *          description="identifiant du theme", 
 *      ),
 *      @OA\Property(
 *          property="type", 
 *          type="string", 
 *          description="Type de la carte ('macarte', 'mesadresses', statistic', storymap')", 
 *      ),
 *      @OA\Property(
 *          property="premium", 
 *          type="string", 
 *          description="Premium de la carte ('default', 'edugeo')", 
 *          default="default",
 *      ),
 *      @OA\Property(
 *          property="active", 
 *          type="boolean", 
 *          description="Carte active (par ex : terminée ou non)"
 *      ),
 *      @OA\Property(
 *          property="share", 
 *          type="string", 
 *          description="Publication de la carte ('atlas', 'private')",
 *          default="private"
 *      ),
 *      @OA\Property(property="bbox", ref="#/components/schemas/Bbox"),
 *      @OA\Property(
 *          property="img_url", 
 *          type="string|null", 
 *          description="Url de l'image illustrant la carte",
 *          format="uri",
 *      ),
 * )
 * 
 * @OA\Schema(
 *      schema="Map_list",
 *      description="Carte",
 *      @OA\Property(type="string", property="title"),
 *      @OA\Property(type="string", property="title_url", description="Titre de la carte, adapté pour etre utilisé dans les url"),
 *      @OA\Property(type="string", property="description", nullable=true),
 *      @OA\Property(type="string", property="theme", nullable=true),
 *      @OA\Property(type="integer", property="theme_id", nullable=true),
 *      @OA\Property(type="string", property="type"),
 *      @OA\Property(type="string", property="premium"),
 *      @OA\Property(type="string", property="created_at", format="date-time"),
 *      @OA\Property(type="string", property="updated_at", format="date-time"),
 *      @OA\Property(type="integer", property="nb_view"),
 *      @OA\Property(type="string", property="img_url", nullable=true, format="uri",),
 *      @OA\Property(type="string", property="share"),
 *      @OA\Property(property="bbox", ref="#/components/schemas/Bbox"),
 *      @OA\Property(type="string", property="view_id"),
 *      @OA\Property(type="string", property="view_url", description="lien vers la visualisation de la carte"),
 *      @OA\Property(type="string", property="edit_id", description="si auteur de la carte"),
 *      @OA\Property(type="string", property="author"),
 * )
 * 
 * @OA\Schema(
 *      schema="Map_view",
 *      description="Carte",
 *      allOf={ @OA\Schema(ref="#/components/schemas/Map_list") },
 *      @OA\Property(
 *          type="string", 
 *          property="data_url", 
 *          description="lien vers le contenu de la carte",
 *          format="uri",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="data_edit_url", 
 *          description="lien vers l'édition du contenu de la carte, si auteur de la carte",
 *          format="uri",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="editor", 
 *          description="utilisateur qui a modifié le contenu, renvoyé si auteur de la carte"
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="creator_id", 
 *          description="identifiant de l'utilisateur qui a créé le contenu, renvoyé si auteur de la carte"
 *      ),
 * )
 */
class MapNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;
    private $container;

    public function __construct(ObjectNormalizer $normalizer, ContainerInterface $container)
    {
        $this->normalizer = $normalizer;
        $this->container = $container;
    }

    /**
     * @var Map $map
     */
    public function normalize($map, $format = null, array $context = []): array
    {
        $router = $this->container->get('router');

        /** @var Map $map */
        $data['title'] = $map->getTitle();
        $data['title_url'] = $this->cleanString($map->getTitle());
        $data['description'] = $map->getDescription();
        $data['theme'] = $map->getTheme() ? $map->getTheme()->getName() : null;
        $data['theme_id'] = $map->getTheme() ? $map->getTheme()->getId() : null;
        $data['type'] = $map->getType();
        $data['premium'] = $map->getPremium();
        $data['active'] = $map->isActive();
        $data['valid'] = $map->isValid();
        $data['created_at'] = $map->getCreatedAt()->format(\DateTimeInterface::W3C);
        $data['updated_at'] = $map->getUpdatedAt() ? $map->getUpdatedAt()->format(\DateTimeInterface::W3C) : '';
        $data['nb_view'] = $map->getNbView();
        $data['user'] = $map->getCreator()->getPublicName();
        $data['img_url'] =  preg_replace(
            '/^http:/',
            'https:', 
            $map->getImgUrl()
        );
        $data['share'] = $map->getShare();
        $data['bbox'] = array(
            floatval($map->getLonMax()),
            floatval($map->getLatMax()),
            floatval($map->getLonMin()),
            floatval($map->getLatMin()),
        );
        $data['author'] = $map->getCreator() ? $map->getCreator()->getPublicName() : '';

        $data['view_id'] = $map->getIdView();
        $data['view_url'] =  preg_replace(
            '/^http:/',
            'https:', 
            $router->generate('api_map_get_view', ['id' => $map->getIdView()], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $data['data_url'] = preg_replace(
            '/^http:/',
            'https:', 
            $router->generate('api_map_get_view_data', array('id' => $map->getIdView()), UrlGeneratorInterface::ABSOLUTE_URL)
        );

        if($context['context'] == 'edit' OR $map->getCreator() == $context['user']){
            $data['creator_id'] = $map->getCreator()->getId();
            $data['edit_id'] = $map->getIdEdit();
            $data['data_edit_url'] = preg_replace(
                '/^http:/',
                'https:', 
                $router->generate('api_map_get_edit_data', array('id' => $map->getIdEdit()), UrlGeneratorInterface::ABSOLUTE_URL)
            );
            $data['editor'] = $map->getEditor() ? $map->getEditor()->getPublicName() : '';
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \App\Entity\Map;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * @var string $string
     * enlève les accents et remplaces les caractères spéciaux par underscore
     */
    private function cleanString($string){
        $unwanted_array = array( ' ' => '_',   'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' 
        );
        //titre sans accent
        $string = strtr( $string, $unwanted_array );
        // titre sans caractère spéciaux
        $string = preg_replace('/[^a-zA-Z0-9-]/', '_', $string);

        return $string;
    }
}
