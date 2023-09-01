<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;

/*
 * Lors de l'authentification par JWT (pas le rafraichissement), on met à jour last login de l'utilisateur 
 */
class AppAuthenticationSuccessHandler extends AuthenticationSuccessHandler{

    private $em;
    /**
     * @param iterable|JWTCookieProvider[] $cookieProviders
     */
    public function __construct(
        JWTTokenManagerInterface $jwtManager, 
        EventDispatcherInterface $dispatcher, 
        ManagerRegistry $doctrine,
        $cookieProviders = []
    )
    {
        $this->em = $doctrine->getManager();
        parent::__construct($jwtManager, $dispatcher, $cookieProviders, false);//$removeTokenFromBodyWhenCookiesUsed);
    }
    
    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $user->setLastLogin(new \DateTime());
        $user->setLoginAttempt(0);

        $this->em->persist($user);
        $this->em->flush();

        return $this->handleAuthenticationSuccess($token->getUser());
    }
}