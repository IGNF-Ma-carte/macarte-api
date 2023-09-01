<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\RandomService;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/creer-un-compte", name="app_register")
     */
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager, 
        MailerInterface $mailer
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setConfirmationToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
            $user->setPublicName($user->getUsername());

            $randomService = new RandomService();
            $string = '';
            do{
                $string = $randomService->getRandomString(4);
            } while( $this->userRepository->findOneBy(['publicId' => $string]) );
            $user->setPublicId($string);

            $this->userRepository->persist($user);

            $email = new TemplatedEmail();
            $email
                ->from(new Address('ne-pas-repondre@ign.fr', "L'équipe MaCarte"))
                ->to($user->getEmail())
                ->subject('[macarte - IGN] Confirmer votre email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context(array(
                    'user' => $user
                ))
            ;

            $mailer->send($email);

            $this->addFlash('success', 'Un email a été envoyé à l\'adresse renseignée');

            return $this->redirectToRoute('default_home');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/creer-un-compte/confirmation/{token}", name="app_register_confirm")
     */
    public function confirm(string $token): Response
    {
        $user = $this->userRepository->findOneByConfirmationToken( $token);

        if (null === $user) {
            $this->addFlash('error', 'L\'utilisateur avec le jeton de confirmation "'.$token.' n\'existe pas');
            return $this->redirectToRoute('default_home');
        }
        
        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $this->userRepository->persist($user);

        return $this->render('registration/confirmed.html.twig', [
            'user' => $user,
        ]);
    }

}
