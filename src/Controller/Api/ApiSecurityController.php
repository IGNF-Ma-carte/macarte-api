<?php

namespace App\Controller\Api;

use OpenApi\Annotations as OA;
use App\Repository\RefreshTokenRepository;
use App\Controller\Api\ApiAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ApiSecurityController extends ApiAbstractController
{

    private $refreshTokenRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * @Route("/api/login", name="api_login", methods="POST")
     * 
     * @OA\Post(
     *      tags={"Login"},
     *      path="/api/login",
     *      @OA\Parameter(
     *          name="username",
     *          in="query",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="password",
     *          in="query",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response="200",
     *          @OA\JsonContent(ref="#/components/schemas/Login"),
     *          description="Jeton Jwt et rafraichissement"
     *      ),
     *      @OA\Response(
     *          response="401",
     *          @OA\JsonContent(
     *              @OA\Property(property="code", type="integer", example="401"),
     *              @OA\Property(property="message", type="string", example="Bad credentials"),
     *          ),
     *          description="Erreur dans le nom d'utilisateur ou le mot de passe"
     *      ),
     *      @OA\Response(
     *          response="429",
     *          @OA\JsonContent(
     *              @OA\Property(property="code", type="integer", example="429"),
     *              @OA\Property(property="message", type="string", example="5 tentatives de connexion échouées, allez sur /debloquer-mon-compte"),
     *          ),
     *          description="Trop de tentatives de connexion échouées"
     *      ),
     * )
     * 
     * @OA\Post(
     *      tags={"Login"},
     *      path="/api/token/refresh",
     *      @OA\Parameter(
     *          name="refresh_token",
     *          in="query",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response="200",
     *          @OA\JsonContent(ref="#/components/schemas/Login"),
     *          description="Jeton JWT et refresh"
     *      ),
     *      @OA\Response(
     *          response="401",
     *          @OA\JsonContent(
     *              @OA\Property(property="code", type="integer", example="401"),
     *              @OA\Property(property="message", type="string", example="An authentication exception occurred"),
     *          ),
     *          description="An authentication exception occurred"
     *      )
     * )
     */
    public function login(): Response
    {
        return new Response('ok');
    }

    /**
     * @Route("/api/logout", name="api_logout", methods="POST")
     * 
     * @OA\Post(
     *      tags={"Login"},
     *      path="/api/logout",
     *      security={"bearer"},
     *      @OA\Response(
     *          response="200",
     *          description="logout"
     *      ),
     *      @OA\Response(response="401", ref="#/components/responses/NotConnected"),
     * )
     */
    public function logout(Request $request): Response
    {
        $user = $this->getUser();
        if(!$user){
            return $this->returnResponse('not connected', Response::HTTP_UNAUTHORIZED);
        }
        
        $this->refreshTokenRepository->removeAllForUser($user);
        
        //suppression de la session php
        $session = $request->getSession();
        $session->invalidate(1);

        return $this->returnResponse('logout', Response::HTTP_OK);
    }

}

