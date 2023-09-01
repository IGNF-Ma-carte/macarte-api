<?php

namespace App\Serializer\Normalizer;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;

/**
 * @todo MISE A JOUR DE LA BASE => Modifier la function researchUpdateMapAttributes
 * 
 * @OA\Schema(
 *      schema="Map_research",
 *      description="Recherche des cartes",
 *      @OA\Property(property="maps", type="array", @OA\Items(ref="#/components/schemas/Map_list")),
 *      @OA\Property(property="themes", type="array", @OA\Items(ref="#/components/schemas/Theme")),
 *      @OA\Property(
 *          property="users", 
 *          type="array", 
 *          @OA\Items(
 *              @OA\Property(property="user", type="string", description="Nom public de l'utilisateur"),
 *              @OA\Property(property="count", type="integer", description="Nombre de cartes")
 *          )
 *      ),
 *      @OA\Property(
 *          property="types", 
 *          type="array", 
 *          @OA\Items(
 *              @OA\Property(property="type", type="string", description="Nom du type"),
 *              @OA\Property(property="count", type="integer", description="Nombre de cartes")
 *          )
 *      ),
 *      @OA\Property(
 *          property="premiums", 
 *          type="array", 
 *          @OA\Items(
 *              @OA\Property(property="premium", type="string", description="Nom du premium"),
 *              @OA\Property(property="count", type="integer", description="Nombre de cartes")
 *          )
 *      ),
 *      @OA\Property(
 *          property="actives", 
 *          type="array", 
 *          @OA\Items(
 *              @OA\Property(property="active", type="boolean", description="booléen indiquant active/inactive"),
 *              @OA\Property(property="count", type="integer", description="Nombre de cartes")
 *          )
 *      ),
 *      @OA\Property(
 *          property="valides", 
 *          type="array", 
 *          @OA\Items(
 *              @OA\Property(property="valid", type="boolean", description="booléen indiquant valide/invalide"),
 *              @OA\Property(property="count", type="integer", description="Nombre de cartes")
 *          )
 *      ),
 *      @OA\Property(
 *          property="shares", 
 *          type="array", 
 *          @OA\Items(
 *              @OA\Property(property="share", type="string", description="Publication de la carte"),
 *              @OA\Property(property="count", type="integer", description="Nombre de cartes")
 *          )
 *      ),
 *      @OA\Property(property="query", type="string", description="Mot(s) contenu(s) dans le titre, la description ou le theme des cartes"),
 *      @OA\Property(property="count", type="integer", description="Nombre de cartes correspondant à la recherche"),
 *      @OA\Property(property="limit", type="integer", description="Nombre de cartes recues dans la requete"),
 *      @OA\Property(property="offset", type="integer", description="Nombre de cartes à passer avant de ls inclure dans la requete"),
 * )
 */
class ResearchNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;
    private $container;

    public function __construct(ObjectNormalizer $normalizer, ContainerInterface $container)
    {
        $this->normalizer = $normalizer;
        $this->container = $container;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        unset($object['normalizer']);
        foreach($object['maps'] as $index => $map){
            $map = $this->researchUpdateMapAttributes($map, $context);
            $object['maps'][$index] = $map;
        }

        return $object;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        if( is_array($data) and isset($data['normalizer']) and $data['normalizer'] == 'research' ){
            //on est dans le cas d'une recherche
            return true;
        }
        return false;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    private function researchUpdateMapAttributes($map, $context){
        $serializer = new Serializer(array(new DateTimeNormalizer()));

        $router = $this->container->get('router');

        $result = array(
            'type' => $map['type'],
            'title' => $map['titre'],
            'description' => $map['description'],
            'updated_at' => $serializer->normalize(new \DateTime($map['maj'])),
            'nb_view' => $map['nb_view'],
            'img_url' =>  preg_replace(
                '/^http:/',
                'https:', 
                $map['img_url']
            ),
            'user' => $map['user'],
            'user_id' => $map['user_id'],
            'theme' => $map['theme'],
            'theme_id' => $map['theme_id'],
            'share' => $map['share'],
            'premium' => $map['premium'],
            'valid' => $map['valide'],
            'active' => $map['active'],
            'title_url' => $this->cleanString($map['titre']),
            'view_id' => $map['id_unique_iframe'],
            'view_url' =>  preg_replace(
                '/^http:/',
                'https:', 
                $router->generate('api_map_get_view', ['id' => $map['id_unique_iframe']], UrlGeneratorInterface::ABSOLUTE_URL)
            ),
            'bbox' => array(
                floatval($map['lonmax']),
                floatval($map['latmax']),
                floatval($map['lonmin']),
                floatval($map['latmin']),
            ),
        );

        if($context['context'] == 'profile'){
            $result['edit_id'] = $map['id_unique'];
            $result['edit_url'] =  preg_replace(
                '/^http:/',
                'https:', 
                $router->generate('api_map_edit', ['id' => $map['id_unique']], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }

        return $result;
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
