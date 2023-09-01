<?php

namespace App\Controller\Api;

use App\Entity\User;
use OpenApi\Annotations as OA;
use App\Controller\Api\ApiAbstractController;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiMeController extends ApiAbstractController
{
    private $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/api/me", name="api_user_me", methods="GET")
     * 
     * @OA\Get(
     *      tags={"User"},
     *      path="/api/me",
     *      security={"bearer"},
     *      @OA\Response(
     *          response="200",
     *          description="Attributs de l'utilisateur connecté",
     *          @OA\JsonContent(ref="#/components/schemas/User_view")
     *      ),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected")
     * )
     */
    public function me(SerializerInterface $serializer): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if(!$user){
            return $this->returnResponse('Not connected', Response::HTTP_UNAUTHORIZED);
        }

        $json = $serializer->serialize($user, 'json', array('context' => 'view'));

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/api/me", name="api_me_patch", methods="PATCH")
     * 
     * @OA\Patch(
     *      tags={"User"},
     *      path="/api/me",
     *      security={"bearer"},
     *      description="parameter **current_password** is required to patch username, email and new_password<br>
                username, email et public_name sont uniques, la réponse 400 '... already used' peut être renvoyée
     ",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/User_me_edit"),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Attributs de l'utilisateur",
     *          @OA\JsonContent(ref="#/components/schemas/User_view")
     *      ),
     *      @OA\Response(response="400", ref="#/components/responses/BadRequest"),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     * )
     */
    public function patch(
        Request $request, 
        SerializerInterface $serializer,
        UserPasswordHasherInterface $userPasswordHasherInterface,
        UserRepository $userRepository
    ): Response
    {
        $content = $request->getContent();
        $data = json_decode($content);
        
        /** @var User $user */
        $user = $this->getUser();

        // les profs et eleves edugeo n'entrent pas de mot de passe, ils ne peuvent pas modifier username, password et email
        $validPassword = false;
        if(isset($data->current_password)){
            $validPassword = $userPasswordHasherInterface->isPasswordValid($user, $data->current_password);
        }

        if(isset($data->username) and $data->username){
            if(preg_match('/(^\s)|(\s$)/', $data->username) ){
                return $this->returnResponse("Username can't start nor end with whitespace, nor be empty", Response::HTTP_BAD_REQUEST);
            }
            if(!$validPassword){
                return $this->returnResponse("Invalid password", Response::HTTP_BAD_REQUEST);
            }
            $user2 = $userRepository->findOneBy(array('username' => $data->username));
            if($user2 and $user2 != $user){
                return $this->returnResponse("Username already used", Response::HTTP_BAD_REQUEST);
            }
            $user->setUsername($data->username);
        }
        if(isset($data->email) and $data->email){
            /** @todo envoyer un mail à l'ancienne adresse */
            if(!$validPassword){
                return $this->returnResponse("invalid password", Response::HTTP_BAD_REQUEST);
            }
            if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)){
                return $this->returnResponse("email value is not valid", Response::HTTP_BAD_REQUEST);
            }
            $user2 = $userRepository->findOneBy(array('email' => $data->email));
            if($user2 and $user2 != $user){
                return $this->returnResponse("email already used", Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail($data->email);
        }
        if(isset($data->new_password)){
            if(!$validPassword){
                return $this->returnResponse("invalid password", Response::HTTP_BAD_REQUEST);
            }
            if(!preg_match(
                "/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\s\-!$%^&@*()_+|~=`{}\[\]:;'<>?,.\/]).{10,}/",
                $data->new_password
            )){
                return $this->returnResponse(
                    "password must contain at least 10 characters, among this at least 1 lower letter, 1 upper letter, one numeral and one special", 
                    Response::HTTP_BAD_REQUEST
                );
            }
            $encodedPassword = $userPasswordHasherInterface->hashPassword(
                $user,
                $data->new_password
            );
            $user->setPassword($encodedPassword);
        }

        if(isset($data->presentation)){
            $user->setPresentation(strip_tags($data->presentation));
        }

        if(isset($data->profile_picture)){
            if($data->profile_picture != "" and !filter_var($data->profile_picture, FILTER_VALIDATE_URL)){
                return $this->returnResponse("profile_picture is not a correct url", Response::HTTP_BAD_REQUEST);
            }
            $user->setProfilePicture($data->profile_picture);
        }

        if(isset($data->cover_picture)){
            if($data->cover_picture != "" and !filter_var($data->profile_picture, FILTER_VALIDATE_URL)){
                return $this->returnResponse("cover_picture is not a correct url", Response::HTTP_BAD_REQUEST);
            }
            $user->setCoverPicture($data->cover_picture);
        }

        if(isset($data->public_name)){
            if($data->public_name == ""){
                return $this->returnResponse("public_name can't be empty", Response::HTTP_BAD_REQUEST);
            }
            $user2 = $userRepository->findOneBy(array('publicName' => $data->public_name));
            if($user2 and $user2 != $user){
                return $this->returnResponse("public_name already used", Response::HTTP_BAD_REQUEST);
            }
            $user->setPublicName(strip_tags($data->public_name));
        }
        
        if(isset($data->twitter_account)){
            $user->setTwitterAccount(strip_tags($data->twitter_account));
        }

        if(isset($data->facebook_account)){
            $user->setFacebookAccount(strip_tags($data->facebook_account));
        }

        if(isset($data->linkedin_account)){
            $user->setLinkedInAccount(strip_tags($data->linkedin_account));
        }

        $this->userRepository->persist($user);

        $json = $serializer->serialize($user, 'json', array('context' => 'view'));

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }
}
