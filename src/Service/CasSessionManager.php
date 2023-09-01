<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\RefreshToken;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\RefreshTokenRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of FileUploader
 *
 * @author DMoreau
 */
class CasSessionManager {

    private $container;
    private $doctrine;
    private $sessionDir;

    public function __construct(ContainerInterface $container, ManagerRegistry $doctrine){
        $this->container = $container;
        $this->doctrine = $doctrine;
        $logDir = $this->container->getParameter('kernel.logs_dir');
        $env = $this->container->getParameter('environment');
        $this->sessionDir = dirname($logDir).DIRECTORY_SEPARATOR.'sessions'.DIRECTORY_SEPARATOR.$env;
        $this->ticketDir = dirname($logDir).DIRECTORY_SEPARATOR.'ticketgar';
        
        if(!file_exists($this->sessionDir)){
            mkdir($this->sessionDir);
        }
        if(!file_exists($this->ticketDir)){
            mkdir($this->ticketDir);
        }
    }

    public function saveSession($sessionId, $userId, $hashedTicket){
        $obj = new \StdClass();
        $obj->sessionId = $sessionId;
        $obj->userId = $userId;

        $content = json_encode($obj);

        $path = $this->ticketDir.DIRECTORY_SEPARATOR.$hashedTicket;
        $handle = fopen($path, "w");
        fwrite($handle, $content);
        fclose($handle);
        /** @todo modifier mode si besoin */
        // chmod($path, 0755);
    }

    public function destroySession($hashedTicket){

        $ticketFile = $this->ticketDir.DIRECTORY_SEPARATOR.$hashedTicket;

        $content = file_get_contents($ticketFile);
        $obj = json_decode($content);

        $userRepository = $this->doctrine->getRepository(User::class);
        $user = $userRepository->find($obj->userId);

        /** @var RefreshTokenRepository $refreshTokenRepository*/
        $refreshTokenRepository = $this->doctrine->getRepository(RefreshToken::class);
        $refreshTokenRepository->removeAllForUser($user);

        $sessionFile = $this->sessionDir.DIRECTORY_SEPARATOR.'sess_'.$obj->sessionId;

        /** @todo detruire la session */
        unlink($ticketFile);
        unlink($sessionFile);
    }

}