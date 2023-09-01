<?php

namespace App\Controller\Api;

use App\Entity\User;
use OpenApi\Annotations as OA;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\RefreshTokenRepository;
use App\Controller\Api\ApiAbstractController;
use App\Entity\RefreshToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class ApiUserController extends ApiAbstractController
{
    private $userRepository;
    private $refreshTokenRepository;
    
    public function __construct(UserRepository $userRepository, RefreshTokenRepository $refreshTokenRepository)
    {
        $this->userRepository = $userRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * @Route("/admin/api/users", options={"expose"=true}, name="admin_api_user_research")
     * 
     */
    public function research(Request $request, SerializerInterface $serializer): Response
    {
        $criteria = array(
            'id' => $request->get('id'),
            'query' => $request->get('query'),
            'role' => $request->get('role'),
            'locked' => $request->get('locked'),
            'limit' => intval($request->get('limit')) ?: 15,
            'offset' => intval($request->get('offset')) ?: 0,
        );
        $result = $this->userRepository->research($criteria);
        $result['normalizer'] = 'user';

        $json = $serializer->serialize($result, 'json');

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/api/users/public/{publicId}", name="api_user_public", methods={"GET"})
     * 
     * @OA\Get(
     *      tags={"User"},
     *      path="/api/users/public/{public_id}",
     *      @OA\Parameter(
     *          name="publicname",
     *          in="path",
     *          required=true,
     *          description="Identifiant public (immuable) de l'utilisateur",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response="200",
     *          @OA\JsonContent(ref="#/components/schemas/User_public"),
     *          description="Attributs publics de l'utilisateur"
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")
     * )
     */
    public function public(string $publicId, SerializerInterface $serializer): Response
    {
        $user = $this->userRepository->findOneBy(array('publicId' => $publicId));

        if(!$user){
            return $this->returnResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        $json = $serializer->serialize($user, 'json', array('context' => 'public'));
        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/admin/api/users/{id}/{attribute}", options={"expose"=true}, name="admin_api_user_attribute_put", methods="PUT")
     */
    public function put($id, $attribute, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        if(!$user){
            return $this->returnResponse('User not found', Response::HTTP_NOT_FOUND);
        }
        
        $content = json_decode($request->getContent());
        $value = $content->value;

        switch($attribute){
            case 'locked':
                $value = filter_var($value, FILTER_VALIDATE_BOOL);
                $user->setLocked($value);
                
                if($value){
                    //supprimer tous les refresh_tokens de l'utilisateur pour l'empecher de récupérer un JWToken valide
                    $this->refreshTokenRepository->removeAllForUser($user);
                }
                break;
            case 'email':
                if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $this->returnResponse('invalid email value', Response::HTTP_BAD_REQUEST);
                }
                $user2 = $this->userRepository->findOneBy(array('email' => $value));
                if($user2 and $user2 != $user){
                    return $this->returnResponse('email already used', Response::HTTP_BAD_REQUEST);
                }
                $user->setEmail($value);
                break;
            case 'username':
                if(!$value){
                    return $this->returnResponse('value required', Response::HTTP_BAD_REQUEST);
                }
                $user2 = $this->userRepository->findOneBy(array('username' => $value));
                if($user2 and $user2 != $user){
                    return $this->returnResponse('username already used', Response::HTTP_BAD_REQUEST);
                }
                $user->setUsername($value);
                break;
            case 'public_name':
                if(!$value){
                    return $this->returnResponse('value required', Response::HTTP_BAD_REQUEST);
                }
                $user2 = $this->userRepository->findOneBy(array('publicName' => $value));
                if($user2 and $user2 != $user){
                    return $this->returnResponse('public_name already used', Response::HTTP_BAD_REQUEST);
                }
                $user->setPublicName($value);
                break;
            case 'presentation':
                $user->setPresentation($value);
                break;
            case 'twitter_account':
                $value = str_replace('https://www.twitter.com/', '', $value);
                $value = str_replace('https://twitter.com/', '', $value);
                $user->setTwitterAccount($value);
                break;
            case 'facebook_account':
                $value = str_replace('https://www.facebook.com/', '', $value);
                $user->setFacebookAccount($value);
                break;
            case 'linkedin_account':
                $value = str_replace('https://linkedin.com/in/', '', $value);
                $user->setLinkedinAccount($value);
                break;
            case 'profile_picture':
                if($value and !filter_var($value, FILTER_VALIDATE_URL)) {
                    return $this->returnResponse('invalid url value', Response::HTTP_BAD_REQUEST);
                }
                $user->setProfilePicture($value);
                break;
            case 'roles': 
                $user->setRoles($value);
                break;
            default:
                return $this->returnResponse('attribute not used', Response::HTTP_BAD_REQUEST);
        }
        $this->userRepository->persist($user);

        return new JsonResponse(array(
            'id' => $user->getId(),
            'attribute' => $attribute,
            'value' => $value
        ), Response::HTTP_OK);
    }

}
