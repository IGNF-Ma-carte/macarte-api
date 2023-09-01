<?php

namespace App\Controller;

use DateTime;
use stdClass;
use App\Entity\Article;
use App\Service\ConfigApi;
use App\Form\ContactFormType;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use App\Repository\ArticleRepository;
use App\Repository\NotificationRepository;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\CasSessionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefaultController extends AbstractController
{
    private $articleRepository;
    private $userRepository;
    private $notificationRepository;
    
    public function __construct(
        ArticleRepository $articleRepository, 
        UserRepository $userRepository, 
        NotificationRepository $notificationRepository
    )
    {
        $this->articleRepository = $articleRepository;
        $this->userRepository = $userRepository;
        $this->notificationRepository = $notificationRepository;
    }
    
    /**
     * @Route("/", name="default_home")
     */
    public function home(): Response
    {
        $repository = $this->articleRepository;
        $titleArticle = $repository->findOneBy(
            [
                'category' => Article::CATEGORY_HOME_TITLE 
            ],
            ['position' => 'ASC']
        );
        $buttonArticles = $repository->findBy(
            [
                'category' => Article::CATEGORY_HOME_BUTTON, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );
        $newsArticles = $repository->findBy(
            [
                'category' => Article::CATEGORY_NEWS, 
                'status' => Article::STATUS_PUBLISHED,
            ], 
            ['position' => 'ASC']
        );
        $advantage1Articles = $repository->findBy(
            [
                'category' => Article::CATEGORY_ADVANTAGE_1, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );
        $advantage2Articles = $repository->findBy(
            [
                'category' => Article::CATEGORY_ADVANTAGE_2, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );
        $testimonyArticles = $repository->findBy(
            [
                'category' => Article::CATEGORY_TESTIMONY, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );

        $notifs = $this->notificationRepository->findByActive(new DateTime());

        foreach( $notifs as $notif){
            $this->addFlash($notif->getType(), $notif->getDescription());
        }

        return $this->render('default/home.html.twig', [
            'titleArticle' => $titleArticle,
            'buttonArticles' => $buttonArticles,
            'newsArticles' => $newsArticles,
            'advantage1Articles' => $advantage1Articles,
            'advantage2Articles' => $advantage2Articles,
            'testimonyArticles' => $testimonyArticles,
        ]);
    }

    /**
     * @Route("/accueil-edugeo", name="default_home_edugeo")
     */
    public function edugeo(Request $request, CasSessionManager $casManager, ContainerInterface $container): Response
    {
        if($this->isGranted('ROLE_EDUGEO_PROF') OR $this->isGranted('ROLE_EDUGEO_ELEVE')){
	        // Sauvegarder la correspondance ticket / session sf
            $casManager = new CasSessionManager($container, $this->getDoctrine());
            $casManager->saveSession($request->getSession()->getId(), $this->getUser()->getId(),$_SESSION['ticket']);
        }

        $titleArticle = $this->articleRepository->findOneBy(
            [
                'category' => Article::CATEGORY_EDUGEO_TITLE 
            ],
            ['position' => 'ASC']
        );
        $buttonArticles = $this->articleRepository->findBy(
            [
                'category' => Article::CATEGORY_EDUGEO_BUTTON, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );

        $articles1 = $this->articleRepository->findBy(
            [
                'category' => Article::CATEGORY_EDUGEO_1, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );
        $articles2 = $this->articleRepository->findBy(
            [
                'category' => Article::CATEGORY_EDUGEO_2, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );

        return $this->render('default/edugeo.html.twig', [
            'titleArticle' => $titleArticle,
            'buttonArticles' => $buttonArticles,
            'articles1' => $articles1,
            'articles2' => $articles2,
        ]);
    }

    /**
     * @Route("/debloquer-mon-compte", name="default_unlock_account")
     */
    public function unlockAccount(Request $request): Response
    {
        $form = $this->createFormBuilder()
        ->add('username', TextType::class, [
            'label' => "Nom d'utilisateur",
        ])
        ->add('captcha', CaptchaType::class, [
            'label' => "Réécrivez les caractères",
            'reload' => true,
            'as_url' => true,
            'attr' => array(
                'autocomplete' =>  'off',
            ),
            'row_attr' => [
                'class' => 'captcha'
            ],
        ])
        ->add('submit', SubmitType::class, [
            'label' => 'Envoyer',
            'attr' => [
                'class' => "btn--plain btn--primary btn"
            ]
        ])
        ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $this->userRepository->findOneBy(['username' => $data['username'] ]);

            $user->setLoginAttempt( $user->getLoginAttempt() - 1);
            $this->userRepository->persist($user);

            $this->addFlash('success', 'Vous pouvez refaire une tentative de connexion');
        }

        
        return $this->render('default/unlock_account.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/cgu", name="default_cgu")
     */
    public function cgu(): Response
    {
        $articles = $this->articleRepository->findBy(
            [
                'category' => Article::CATEGORY_CGU, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );

        return $this->render('default/cgu.html.twig', [
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/mentions-legales", name="default_mentions_legales")
     */
    public function mentionsLegales(): Response
    {
        $articles = $this->articleRepository->findBy(
            [
                'category' => Article::CATEGORY_MENTION, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );

        return $this->render('default/mentions.html.twig', [
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/cookies-et-statistiques", name="default_cookie")
     */
    public function cookies(): Response
    {
        $articles = $this->articleRepository->findBy(
            [
                'category' => Article::CATEGORY_COOKIE, 
                'status' => Article::STATUS_PUBLISHED
            ], 
            ['position' => 'ASC']
        );

        return $this->render('default/cookie.html.twig', [
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/nous-contacter", name="default_contact")
     * @Route("/signaler", name="default_report")
     */
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if($this->isGranted('ROLE_EDUGEO_ELEVE') OR $this->isGranted('ROLE_EDUGEO_PROF')){
            return $this->redirect('https://www.edugeo.fr/contact');
        }
        
        $route = $request->get('_route');

        if(
            $request->query->get('service') !== null
            OR $request->query->get('user') !== null
            OR $request->query->get('userID') !== null
            OR $request->query->get('email') !== null
            OR $request->query->get('mapID') !== null
            OR $request->query->get('content') !== null
            OR $request->query->get('subject') !== null
        ){
            
            $params = [
                'service' => $request->get('service'),
                'user' => $request->get('user'),
                'userID' => $request->get('userID'),
                'userEmail' => $request->get('email'),
                'mapID' => $request->get('mapID'),
                'content' => $request->get('content'),
                'subject' => $request->get('subject'),
            ];
            $this->storeDataInSession($params);
            
            return $this->redirectToRoute($route);
        }
        $data = $this->getDataFromSession();
        $this->cleanSession();

        if($route == 'default_report'){
            $choices = [
                'Signaler un contenu indésirable' => 'Signaler un contenu indésirable',
            ];
        }else{
            $choices = [
                'Problème technique' => 'Problème technique',
                'Fonctionnement du site' => 'Fonctionnement du site',
                'Edugéo' => 'Edugéo',
                'Autre' => 'Autre',
            ];
        }
        
        $form = $this->createForm(ContactFormType::class, null, [
            'data' => [  
                'choices' => $choices,
                'service' => isset($data['service']) ? $data['service'] : '',
                'user' => isset($data['user']) ? $data['user'] : '',
                'userID' => isset($data['userID']) ? $data['userID'] : '',
                'userEmail' => isset($data['userEmail']) ? $data['userEmail'] : '',
                'mapID' => isset($data['mapID']) ? $data['mapID'] : '',
                'content' => isset($data['content']) ? $data['content'] : '',
                'subject' => isset($data['subject']) ? $data['subject'] : '',
            ]
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $email = new TemplatedEmail();
            $email
                ->from(new Address('ne-pas-repondre@ign.fr', "L'équipe MaCarte"))
                ->to($data['email'])
                ->bcc($this->getParameter('contact_email'))
                ->subject('[macarte - IGN] ' . $data['subject'])
                ->htmlTemplate('default/contact_email.html.twig')
                ->context(array(
                    'action' => ($route == 'default_report') ? 'report' : 'contact',
                    'data' => $data,
                ))
            ;

            $mailer->send($email);

            $this->addFlash('success', 'Un email a été envoyé à l\'équipe Macarte IGN');
            return $this->redirectToRoute('default_home');
        }

        return $this->render('default/contact.html.twig', [
            'action' => ($route == 'default_report') ? 'report' : 'contact',
            'form' => $form->createView(),
            'mapID' => isset($data['mapID']) ? $data['mapID'] : '',
        ]);

    }

    private function getDataFromSession()
    {
        return $this->getSessionService()->get('MC@contact');
    }

    private function storeDataInSession($data): void
    {
        $this->getSessionService()->set('MC@contact', $data);
    }

    private function getSessionService(): SessionInterface
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();

        return $request->getSession();
    }

    private function cleanSession(): void
    {
        $session = $this->getSessionService();

        $session->remove('MC@contact');
    }

    /**
     * @Route("/config-server.json", name="app_config_json")
     */
    public function configJson(Request $request, ConfigApi $configService): Response
    {
        $host = $request->headers->get('referer');
        $config = $configService->getConfig($host);

        return new JsonResponse($config, Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }
}
