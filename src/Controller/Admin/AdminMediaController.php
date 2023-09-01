<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminMediaController extends AbstractController
{
    /**
     * @Route("/admin/media", name="admin_media_index")
     */
    public function index(Request $request): Response
    {
        
        return $this->render('admin/media/index.html.twig', [
            'username' => $request->get('user')
        ]);
    }

}
