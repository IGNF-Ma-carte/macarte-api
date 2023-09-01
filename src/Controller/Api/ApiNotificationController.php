<?php

namespace App\Controller\Api;

use DateTime;
use App\Repository\NotificationRepository;
use App\Controller\Api\ApiAbstractController;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiNotificationController extends ApiAbstractController
{
    private $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * @Route("/api/notifications", name="api_notification_get", methods={"GET"})
     *
     * @OA\Get(
     *      tags={"Notifications"},
     *      path="/api/notifications",
     *      @OA\Response(
     *          response="200",
     *          description="Liste des notifications actives",
     *          @OA\JsonContent(
     *              type="array", 
     *              @OA\Items(ref="#/components/schemas/Notification")
     *          )
     *      )
     * )
     */
    public function index(SerializerInterface $serializer): Response
    {
        $notifs = $this->notificationRepository->findByActive(new DateTime());

        $json = $serializer->serialize($notifs, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['updatedAt', 'updatedBy']]);

        return new Response($json, Response::HTTP_OK);
    }
}
