<?php

namespace App\Security;

use Exception;
use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Container\ContainerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;

/*
 * Lors de l'authentification par JWT (pas le rafraichissement), on met à jour last login de l'utilisateur 
 */
class AppAuthenticationFailureHandler extends AuthenticationFailureHandler{

    private $em;
    private $container;
    /**
     * @param iterable|JWTCookieProvider[] $cookieProviders
     */
    public function __construct(
        EventDispatcherInterface $dispatcher, 
        ManagerRegistry $doctrine,
        ContainerInterface $container
    )
    {
        $this->em = $doctrine->getManager();
        $this->container = $container;
        parent::__construct($dispatcher);
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        try{
            $maxLoginAttempts = $this->container->getParameter('max_login_attempts');
        }catch(Exception $e){
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        $credentials = json_decode($request->getContent());
        $username = $credentials->username;

        /** @var UserRepository $repository */
        $repository = $this->em->getRepository(User::class);
        $user = $repository->findOneBy(['username' => $username]);

        if($user){
            if($user->getLoginAttempt() > $maxLoginAttempts){
                return new Response('too many login attempts', Response::HTTP_TOO_MANY_REQUESTS); 
            }
            $user->setLoginAttempt($user->getLoginAttempt()+1);
            $repository->persist($user);
        }

        return parent::onAuthenticationFailure($request, $exception);
    }
}