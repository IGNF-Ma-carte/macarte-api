<?php

namespace App\EventListener;

use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppLogoutListener
{
    private $doctrine;
    private $urlGenerator;

    public function __construct(
        ManagerRegistry $doctrine, 
        UrlGeneratorInterface $urlGenerator 
    )
    {
        $this->doctrine = $doctrine;
        $this->urlGenerator = $urlGenerator;
    }
    /**
     * @param LogoutEvent $logoutEvent
     * @return void
     */
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent): void
    {
        if(!$logoutEvent->getToken()){
            return;
        }
        $user = $logoutEvent->getToken()->getUser();

        /** @var RefreshTokenRepository $repository */
        $repository = $this->doctrine->getRepository(RefreshToken::class);
        $repository->removeAllForUser($user);

        $logoutEvent->setResponse(new RedirectResponse($this->urlGenerator->generate('default_home')));
    }
}