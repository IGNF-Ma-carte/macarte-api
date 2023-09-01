<?php

namespace App\Security;

use phpCAS;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class EduthequeAuthenticator extends AbstractAuthenticator
{
    public const LOGIN_ROUTE = 'app_login_edutheque';

    private $container;
    private $doctrine;
    private $urlGenerator;

    public function __construct(
        ContainerInterface $container,
        ManagerRegistry $doctrine, 
        UrlGeneratorInterface $urlGenerator 
    )
    {
        $this->container = $container;
        $this->doctrine = $doctrine;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route');
    }

    /**
     * Etapes : 
     * 1 - Récupération du ticket CAS
     * 2 - Vérification du ticket 
     * 3 - Recherche user, création si non trouvé
     * 4 - Redirection vers accueil 
     */
    public function authenticate(Request $request): Passport
    {
        /*
            structure de $_SESSION
            array:4 [
                "_sf2_attributes" => & array:1 [...]
                "_sf2_meta" => & array:3 [...]
                "_symfony_flashes" => & [...]
                "phpCAS" => array:2 [
                    "user" => "<string email>"
                    "attributes" => array:6 [
                        "nom" => "<string nom>"
                        "prenom" => "<string prenom>"
                        "email" => "<string email>"
                        "fonction" => "<int>"
                        "idUser" => "<int>"
                        "username" => "<string email>"
                    ]
                ]
                "ticket" => "<string>"
            ]

            nom - (pas présent sur le compte classe)
            prénom - (pas présent sur le compte classe)
            email - (pas présent sur le compte classe)
            fonction - type d'utilisateur 3 valeurs possible :
                -> 0 = compte élève
                -> 1 = compte enseignant
                -> 2 = compte classe
            idUser - identifiant unique (? pas sur pour unique) utilisateur
            idparent - utilisé par exemple pour les comptes classes. Exemple le prof créer un compte pour sa classe, le compte classe créé aura pour idParent "<idUser>"
            username - nom d'utilisateur (utilisé par les compte classe aussi)
        */

        $session = $this->getCasTicket();
        $ticket = $session['ticket'];
        if(!$ticket){
            echo('no ticket');
            exit;
        }
        
        $authIsValid = $this->validateCas($ticket);
        
        if(!$authIsValid){
            echo('no valid ticket');
            exit;
        }
        
        $attributes = $session['phpCAS'];
        $username = $attributes['user'];
        /** @var UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername($username);

        if(!$user){
            $user = $this->createUser($attributes);
        }
        return new SelfValidatingPassport(new UserBadge($username));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // TODO: Implement onAuthenticationSuccess() method.
        return new RedirectResponse($this->urlGenerator->generate('default_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // TODO: Implement onAuthenticationFailure() method.
    }

    private function getCasTicket(){
        $logFile = $this->container->getParameter('kernel.logs_dir').DIRECTORY_SEPARATOR.'log_cas_'.date('Y_m_d').'.log';
        \phpCAS::setDebug($logFile);
        \phpCAS::setVerbose(true);
        \phpCAS::client(CAS_VERSION_2_0, 'www.edutheque.fr/cas-edutheque', 443, "");
        \phpCAS::setPostAuthenticateCallback(function ($ticket) {
            $_SESSION['ticket'] = $ticket;
        });

        $proxy = $this->container->getParameter('proxy');
        \phpCAS::setExtraCurlOption(CURLOPT_PROXY, $proxy);
        \phpCAS::setLang('CAS_Languages_French');
        \phpCAS::setNoCasServerValidation();
        \phpCAS::forceAuthentication();

        if (\phpCAS::isAuthenticated()) {
            return $_SESSION;
        }else{
            echo('not authenticated');
            exit;
        }
    }

    private function validateCas($ticket){
        // $garValidateUrl = 'https://idp-auth.partenaire.test-gar.education.fr/p3/serviceValidate?
        //      service=https%3A%2F%2Fpfv-simulateur-dtr.gar.renater.fr%2Fcasclient%2Fdocs%2Fexamples%2Fexample_simple1.php
        //      &ticket=ST-7-NPIpzTy0BdqarA05XqVx-idp-auth.pp.test-gar.education.fr';

        $verifParam = array(
            'service' => $this->urlGenerator->generate(self::LOGIN_ROUTE, array(), UrlGeneratorInterface::ABSOLUTE_URL), 
            'ticket' => $ticket,
            'format' => 'json',
        );

        $verifUrl = 'https://edutheque.fr/cas-edutheque/p3/serviceValidate?'.http_build_query($verifParam);

        $options = array(
            CURLOPT_URL => $verifUrl, 
            CURLOPT_PROXY => $this->container->getParameter('proxy'), 
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_FOLLOWLOCATION => true,   // follow redirects
		); 
    
        $ch = curl_init();
        curl_setopt_array($ch , $options);
        $response = curl_exec($ch);
		curl_close($ch);

		$respObj = json_decode($response);
		$serviceResponse = $respObj->serviceResponse;
		
		if(isset($serviceResponse->authenticationFailure)){
			return false;
		}
		
		if(isset($serviceResponse->authenticationSuccess)){
			return true;
		}

		return false;
    }

    private function createUser($attributes){
        /** @var User $user */
        $user = new User();

        $user->setUsername($attributes['email']);
        $user->setPublicName($attributes['email']);
        $user->setEmail('eduthec_'.$attributes['email']);
        $user->setEnabled(true);
        if($attributes['fonction'] === "1"){
            $user->setRoles(array('ROLE_EDUGEO_PROF'));
        }else{
            $user->setRoles(array('ROLE_EDUGEO_ELEVE'));
        }

        $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $user->setPassword(base64_encode(random_bytes(32)));
        
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();
        
        return $user;
    }

//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}
