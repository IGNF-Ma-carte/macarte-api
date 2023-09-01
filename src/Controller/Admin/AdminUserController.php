<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminUserController extends AbstractController
{
    private $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    /**
     * @Route("/admin/user", name="admin_user_index")
     */
    public function index(): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'nbUsers' => $this->userRepository->countGlobal(),
        ]);
    }

    /**
     * @Route("/admin/user/{id}/voir", options={"expose"=true}, name="admin_user_view")
     */
    public function view($id): Response
    {
        $user = $this->userRepository->find($id);

        return $this->render('admin/user/view.html.twig', [
            'user' => $user,
            'roles' => User::getRoleNames(),
        ]);
    }

    /**
     * @Route("/admin/user/{id}/delete", options={"expose"=true}, name="admin_user_delete")
     */
    public function delete($id): Response
    {
        $user = $this->userRepository->find($id);

        $this->userRepository->remove($user);

        $this->addFlash('success', "Le compte a été supprimé");

        return $this->redirectToRoute('admin_user_index');
    }
}
