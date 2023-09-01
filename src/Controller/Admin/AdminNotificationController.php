<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use App\Form\NotificationFormType;
use App\Repository\NotificationRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/notification")
 */
class AdminNotificationController extends AbstractController
{
    private $notificationRepository;
    
    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * @Route("/", name="admin_notif_index")
     */
    public function index(): Response
    {
        return $this->render('admin/notification/index.html.twig', [
            'notifs' => $this->notificationRepository->findAll(),
        ]);
    }

    /**
     * @Route("/ajouter", name="admin_notif_add")
     */
    public function add(Request $request): Response
    {
        $notif = new Notification();
        $form = $this->createForm(NotificationFormType::class, $notif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notif->setUpdatedBy($this->getUser());
            $notif->setUpdatedAt(new \DateTime ());
            $this->notificationRepository->persist($notif, true);

            return $this->redirectToRoute('admin_notif_index');
        }

        return $this->render('admin/notification/add_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/supprimer/{id}", name="admin_notif_remove")
     */
    public function remove($id): Response
    {
        $article = $this->notificationRepository->find($id);
        $this->notificationRepository->remove($article, true);

        return $this->redirectToRoute('admin_notif_index');
    }
}
