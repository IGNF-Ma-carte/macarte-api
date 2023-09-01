<?php
//src\Security\AppJWTAuthenticator.php

namespace App\Security;
 
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\AccessMap;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
 
/*
 *  autoriser l'accès aux routes publiques (ie routes en GET de l'API) si le token JWt est absent, invalide ou expiré
 *  l'autorisation peut etre vérifiée au cas par cas
 */
final class AppJWTAuthenticator extends JWTAuthenticator
{
    private $accessMap;
    private $eventDispatcher;
 
    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $eventDispatcher,
        TokenExtractorInterface $tokenExtractor,
        UserProviderInterface $userProvider,
        AccessMap $accessMap
    ) {
        parent::__construct($jwtManager, $eventDispatcher, $tokenExtractor, $userProvider);
 
        $this->accessMap = $accessMap;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        //si on est sur une route publique, on ne renvoie pas d'erreur
        list($accessMap) = $this->accessMap->getPatterns($request);
        if (is_array($accessMap) && in_array('PUBLIC_ACCESS', $accessMap, true)) {
            return null;
        }
        
        $errorMessage = strtr($exception->getMessageKey(), $exception->getMessageData());
        $response = new JWTAuthenticationFailureResponse($errorMessage);

        if ($exception instanceof ExpiredTokenException) {
            $event = new JWTExpiredEvent($exception, $response);
            $eventName = Events::JWT_EXPIRED;
        } else {
            $event = new JWTInvalidEvent($exception, $response);
            $eventName = Events::JWT_INVALID;
        }

        $this->eventDispatcher->dispatch($event, $eventName);

        return $event->getResponse();
    }
} 