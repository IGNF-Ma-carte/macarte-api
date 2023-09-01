<?php

namespace App\Controller\Api;

use App\Repository\ThemeRepository;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ApiController extends AbstractController
{
    /**
     * @Route("/api", name="api_index", options={"expose"=true})
     * @Route("/admin/api", name="admin_api_index", options={"expose"=true})
     */
    public function documentation(ThemeRepository $repo): Response
    {

        return $this->render('api/api/documentation.html.twig', 
            array()
        );
    }
}
