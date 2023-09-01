<?php

namespace App\Serializer\Normalizer;

use stdClass;
use App\Entity\Map;
use App\Entity\User;
use OpenApi\Annotations as OA;
use App\Repository\MapRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;

/**
 * @OA\Schema(
 *      schema="Login",
 *      @OA\Property(
 *          type="string", 
 *          property="token", 
 *          description="Jeton JWT à envoyer dans le header Authorization: Bearer xxx",
 *      ),
 *      @OA\Property(
 *          property="refresh_token", 
 *          type="string",
 *          description="Jeton pour rafraichir le jeton JWT à envoyer /api/token/refresh",
 *      ),
 * )
 * 
 * @OA\Schema(
 *      schema="User_public",
 *      @OA\Property(type="string", property="public_id", description="identifiant public de l'utilisateur, ne sera jamais modifié"),
 *      @OA\Property(type="string", property="public_name", description="Unique, ne doit pas être déjà utilisé (Erreur 400)"),
 *      @OA\Property(type="string", property="twitter_account", description="Nom de compte Twitter"),
 *      @OA\Property(type="string", property="facebook_account", description="Nom de compte Facebook"),
 *      @OA\Property(type="string", property="linkedin_account", description="Nom de compte LinkedIn"),
 *      @OA\Property(type="string", property="presentation", description="Présentation publique"),
 *      @OA\Property(type="string", property="profile_picture", format="uri", description="Url de l'image de profil"),
 *      @OA\Property(type="string", property="cover_picture", format="uri", description="Url de l'image de couverture"),
 * )
 * 
 * @OA\Schema(
 *      schema="User_edit",
 *      allOf={ @OA\Schema(ref="#/components/schemas/User_public") },
 *      @OA\Property(type="string", property="email", format="email", description="Unique, ne doit pas être déjà utilisé (Erreur 400)"),
 *      @OA\Property(type="string", property="username", description="Unique, ne doit pas être déjà utilisé (Erreur 400)"),
 * )
 * @OA\Schema(
 *      schema="User_me_edit",
 *      allOf={ @OA\Schema(ref="#/components/schemas/User_edit") },
 *      @OA\Property(type="string", property="current_password"),
 *      @OA\Property(type="string", property="new_password"),
 * )
 * @OA\Schema(
 *      schema="User_view",
 *      allOf={ @OA\Schema(ref="#/components/schemas/User_edit") },
 *      @OA\Property(type="boolean", property="locked", description="Compte bloqué"),
 *      @OA\Property(type="integer", property="id"),
 *      @OA\Property(type="integer", property="medias_limit_size", description="Taille globale limite des images uploadées (en octet)"),
 *      @OA\Property(type="integer", property="medias_size", description="Taille globale des images uploadées (en octet)"),
 *      @OA\Property(type="array", property="roles", @OA\Items(type="string") ),
 * )
 */
class UserNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;
    private $doctrine;

    public function __construct(ObjectNormalizer $normalizer, ManagerRegistry $doctrine)
    {
        $this->normalizer = $normalizer;
        $this->doctrine = $doctrine;
    }

    /**
     * @var User $user
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        if($object instanceof User){
            switch($context['context']){
                case 'public':
                    return $this->getPublicAttributes($object);
                case 'view':
                    return $this->getViewAttributes($object);
            }
        }

        unset($object['normalizer']);

        $users = [];

        foreach($object['users'] as $user){
            $users[] = $this->getViewAttributes($user);
        }

        $result = array(
            "users" => $users,
            "count" => $object['count'],
            "limit" => $object['limit'],
            "offset" => $object['offset'],
        );

        return $result;

    }

    public function supportsNormalization($data, $format = null): bool
    {
        if( is_array($data) and isset($data['normalizer']) and $data['normalizer'] == 'user' ){
            //on est dans le cas d'une liste de users
            return true;
        }

        return $data instanceof \App\Entity\User;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * @var User $user
     */
    private function getPublicAttributes(User $user){
        $repository = $this->doctrine->getRepository(Map::class);
        $serializer = new Serializer(array(new DateTimeNormalizer()));
        
        $registeredAt = $user->getRegisteredAt();

        if(!$registeredAt){
            $userId = $user->getId();
            $firstMap = $repository->findBy(
                array('creator' => $userId),
                array('id' => 'ASC')
            );
            if($firstMap){
                $user->setRegisteredAt($firstMap[0]->getCreatedAt());
                $this->doctrine->getManager()->persist($user);
                $this->doctrine->getManager()->flush();
            }
            $registeredAt = $user->getRegisteredAt();
        }

        $sharedMaps = $repository->findBy(
            array(
                'creator' => $user->getId(),
                'share' => MAP::SHARE_ATLAS
            )
        );

        $nbViews = 0;
        foreach($sharedMaps as $map){
            $nbViews += $map->getNbView();
        }

        $data = array();
        $data['public_name'] = $user->getPublicName();
        $data['public_id'] = $user->getPublicId();
        $data['twitter_account'] = $user->getTwitterAccount();
        $data['linkedin_account'] = $user->getLinkedinAccount();
        $data['facebook_account'] = $user->getFacebookAccount();
        $data['presentation'] = $user->getPresentation();
        $data['profile_picture'] = $user->getProfilePicture();
        $data['cover_picture'] = $user->getCoverPicture();
        $data['registered_at'] = $serializer->normalize($registeredAt);
        $data['nb_shared_maps'] = sizeof($sharedMaps);
        $data['nb_views'] = $nbViews;

        return $data;
    }

    private function getViewAttributes(User $user){
        /** @var User $user */
        $data = $this->getPublicAttributes($user);

        $data['id'] = $user->getId();
        $data['username'] = $user->getUsername();
        $data['email'] = $user->getEmail();
        $data['locked'] = $user->isLocked();
        $data['last_login'] = $user->getLastLogin();
        $data['roles'] = $user->getRoles();
        $data['medias_limit_size'] = $user->getMediasizeLimit();
        $data['medias_size'] = $user->getMediasSize();

        return $data;
    }

}
