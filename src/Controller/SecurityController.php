<?php

namespace App\Controller;

use phpCAS;
use App\Entity\RefreshToken;
use App\Service\CasSessionManager;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\RefreshTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class SecurityController extends AbstractController
{
    private $refreshTokenRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }
    
    /**
     * @Route("/connexion", name="app_login", options={"expose"=true})
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    /**
     * @Route("/deconnexion", name="app_logout", options={"expose"=true})
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * méthode interceptée par \src\Security\EduthequeAuthenticator.php
     * 
     * @Route("/connexion/edutheque", name="app_login_edutheque")
     */
    public function connectionEdutheque(): Response
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the authenticator key on your firewall.');
    }

    /**
     * méthode interceptée par \src\Security\GarAuthenticator.php
     * 
     * @Route("/connexion/gar", name="app_login_gar")
     */
    public function connectionGar(): Response
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the authenticator key on your firewall.');
    }

    /**
     * @Route("/deconnexion/gar", name="app_logout_gar")
     */
    public function logoutGar(ContainerInterface $container, ManagerRegistry $doctrine, Request $request): Response
    {
        // configuration Single Logout
        \phpCAS::client(
            CAS_VERSION_3_0, 
            $this->getParameter('gar_cas'), 
            443, 
            "", 
            $this->getParameter('edugeo_server'), 
            false
        );
        \phpCAS::setServerServiceValidateURL($this->getParameter('gar_cas_validation'));
        $proxy = $this->getParameter('proxy');
        \phpCAS::setExtraCurlOption(CURLOPT_PROXY, $proxy);

        \phpCAS::setSingleSignoutCallback(function($ticket) use ($container, $doctrine) {
            // detruire la session et la correspondance ticket / session sf
            $csm = new CasSessionManager($container, $doctrine);
            $csm->destroySession(hash('sha1',$ticket));
        });

        \phpCAS::handleLogoutRequests(false);

        return $this->redirectToRoute('app_logout');
    }

    /**
     * méthode interceptée par \src\Security\LumniAuthenticator.php
     * 
     * @Route("/connexion/lumni", name="app_login_lumni")
     */
    public function connectionLumni(): Response
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * cette route permet de recevoir les tokens si on est connectés sur le site via une session (prof/eleves ou admin)
     * @Route("/session-token", name="app_session_token", options={"expose"=true})
     */
    public function sessionToken(JWTTokenManagerInterface $jwtManager ): Response
    {
        $user = $this->getUser();
        if(!$user){
            return new Response('no user', Response::HTTP_UNAUTHORIZED);
        }

        $refreshToken = $this->generateRefreshToken($user);
        return new JsonResponse(array(
            'token' => $jwtManager->create($user),
            'refresh_token' => $refreshToken->getRefreshToken(),
        ));
    }

    private function generateRefreshToken($user){
        $refreshToken = new RefreshToken();
        $refreshToken->setUsername($user->getId());
        
        $datetime = new \DateTime();
        $ttl = $this->getParameter('gesdinet_jwt_refresh_token.ttl');
        $datetime->modify('+'.$ttl.' seconds');
        $refreshToken->setValid($datetime);

        $token = '';
        do{
            $token = bin2hex(openssl_random_pseudo_bytes(64));
            $existingToken = $this->refreshTokenRepository->findOneBy(array('refreshToken' => $token));
        }while($existingToken);
        $refreshToken->setRefreshToken($token);

        $this->refreshTokenRepository->persist($refreshToken);

        return $refreshToken;
    }
}
