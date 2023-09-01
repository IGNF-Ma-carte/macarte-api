<?php

namespace App\Controller\Admin;

use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminThemeController extends AbstractController
{
    /**
     * @Route("/admin/theme", name="admin_theme_index")
     */
    public function index(ThemeRepository $repository): Response
    {
        return $this->render('admin/theme/index.html.twig', [
            'themes' => $repository->findBy(array(), array('id' => 'ASC')),
        ]);
    }
}
