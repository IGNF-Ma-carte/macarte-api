<?php

namespace App\Security;

use phpCAS;
use App\Entity\User;
use App\Entity\RefreshToken;
use Psr\Log\LoggerInterface;
use App\Service\RandomService;
use App\Service\CasSessionManager;
use App\Repository\UserRepository;
use App\Repository\RefreshTokenRepository;
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
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use \SessionHandlerInterface;

class GarAuthenticator extends AbstractAuthenticator
{
    public const LOGIN_ROUTE = 'app_login_gar';

    private $container;
    private $doctrine;
    private $urlGenerator;
    private $user;

    public function __construct(
        ContainerInterface $container,
        ManagerRegistry $doctrine, 
        UrlGeneratorInterface $urlGenerator,
        \SessionHandlerInterface $sessionHandler
    )
    {
        $this->container = $container;
        $this->doctrine = $doctrine;
        $this->urlGenerator = $urlGenerator;
        $this->sessionHandler = $sessionHandler;
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
                        "IDO" => "3905f24a97c63a3511a53e33c801533fe0a9aa3c21e29b06f22a96b7bcc49287472c7c0d35413ce15e5a696280edc3151b39c7834e9d4c04e9c1e3a2de6b2511",
                        "idENT" => "WjA=",
                        "IDC" => "ark:/79004/3dvg3080695.pp_3905f24a97c63a3511a53e33c801533fe0a9aa3c21e29b06f22a96b7bcc49287472c7c0d35413ce15e5a696280edc3151b39c7834e9d4c04e9c1e3a2de6b2511",
                        "LRA" => array(
                            "https://minetestqualif.siv.cloud/",
                            "https://edugeo-qualif.isaveSessiongn.fr/"
                        ),
                        "PRO" => "National_ens",
                        "UAI" => "0561534N",
                    ]
                ]
                "ticket" => "<string>"
            ]

            IDO - identifiant de l'utilisateur (enregistré dans username)
            idENT - id de l'ENT de chaque école
            IDC - utilisé pour configurer le GAR
            LRA - Famille de sites
            PRO - Statut de l'utilisateur (national_enseignant, national_eleve)
            UAI - 
        */

        if(isset($_SESSION['_sf2_attributes']['_security_main'])){
            $userTemp = unserialize($_SESSION['_sf2_attributes']['_security_main']);
            $user = $userTemp->getUser();
            /** @var UserRepository $repositoryToken */
            $repositoryToken = $this->doctrine->getRepository(RefreshToken::class);
            $repositoryToken->removeAllForUser($user);
            $request->getSession()->invalidate();
            unset($_SESSION['phpCAS']);
        }

        $session = $this->getCasTicket($request);
        $ticket = $session['ticket'];
        if(!$ticket){
            echo('no ticket');
            exit;
        }
        
        /*$authIsValid = $this->validateCas($ticket);
        
        if(!$authIsValid){
            echo('no valid ticket');
            exit;
        }*/
        
        $attributes = $session['phpCAS']['attributes'];
        $username = $attributes['IDO'];
        /** @var UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername($username);
        $this->user = $user;

        if($user){
            if(substr($user->getPublicName(), 0, 4) != "ENT_"){
                $user->setEmail($username);
                $user->setLoginType('gar');
                $user->setPublicName('ENT_'.uniqid());
                $repository->persist($user, true); 
            }        
        }else{
           $user = $this->createUser($attributes);
           $this->user = $user;
        }

        return new SelfValidatingPassport(new UserBadge($username));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if (\phpCAS::isInitialized()) {
            $token->setAttributes(\phpCAS::getAttributes());
        }

        // TODO: Implement onAuthenticationSuccess() method.
        return new RedirectResponse($this->urlGenerator->generate('default_home_edugeo'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // TODO: Implement onAuthenticationFailure() method.
    }

    private function getCasTicket(Request $request){
        $logFile = $this->container->getParameter('kernel.logs_dir').DIRECTORY_SEPARATOR.'log_cas_'.date('Y_m_d').'.log';
        \phpCAS::setDebug($logFile);
        \phpCAS::setVerbose(true);

        /** @todo mettre l'url du cas */

	// $host = "idp-auth.partenaire.test-gar.education.fr";
        // \phpCAS::client(
        //     CAS_VERSION_3_0, 
        //     $host, 
        //     443, 
        //     "", 
        //     "https://edugeo-qualif.ign.fr"
        //     , false);
        \phpCAS::client(
            CAS_VERSION_3_0, 
            $this->container->getParameter('gar_cas'), 
            443, 
            "", 
            $this->container->getParameter('edugeo_server'), 
            false,
            $this->sessionHandler
        );
        \phpCAS::setPostAuthenticateCallback(function ($ticket) {
            $_SESSION['ticket'] = hash('sha1', $ticket);
        });
        \phpCAS::setServerServiceValidateURL($this->container->getParameter('gar_cas_validation'));
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

        $verifUrl = $this->container->getParameter('gar_cas_validation').'?'.http_build_query($verifParam);

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

    /** @todo update avec la structure gar */
    private function createUser($attributes){
        /** @var User $user */
        $user = new User();

        $user->setUsername($attributes['IDO']);
        $user->setPublicName('ENT_'.uniqid());
        $user->setEmail($attributes['IDO']);
        $user->setLoginType('gar');
        $user->setEnabled(true);
        if($attributes['PRO'] === "National_elv"){
            $user->setRoles(array('ROLE_EDUGEO_ELEVE'));
        }else{
            $user->setRoles(array('ROLE_EDUGEO_PROF'));
        }

        $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $user->setPassword(base64_encode(random_bytes(32)));

        $randomService = new RandomService();
        $repository = $this->doctrine->getRepository(User::class);
        $string = '';
        do{
            $string = $randomService->getRandomString(4);
        } while( $repository->findOneBy(['publicId' => $string]) );
        $user->setPublicId($string);
        
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