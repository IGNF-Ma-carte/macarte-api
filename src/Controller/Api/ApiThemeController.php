<?php

namespace App\Controller\Api;

use App\Entity\Theme;
use OpenApi\Annotations as OA;
use App\Repository\ThemeRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Controller\Api\ApiAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ApiThemeController extends ApiAbstractController
{
    private $themeRepository;
    
    public function __construct(ThemeRepository $themeRepository)
    {
        $this->themeRepository = $themeRepository;
    }

    /**
     * @Route("/api/themes", name="api_theme_get", methods={"GET"})
     * 
     * @OA\Get(
     *      tags={"Theme"},
     *      path="/api/themes",
     *      @OA\Response(
     *          response="200",
     *          description="Liste des themes",
     *          @OA\JsonContent(
     *              type="array", 
     *              @OA\Items(ref="#/components/schemas/Theme")
     *          )
     *      )
     * )
     */
    public function getAll(SerializerInterface $serializer): Response
    {
        $themes = $this->themeRepository->findBy([], ["id" => 'ASC']);

        $json = $serializer->serialize($themes, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['maps']]);

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/admin/api/themes", options={"expose"=true}, name="admin_api_theme_add", methods={"POST"})
     */
    public function add(Request $request, SerializerInterface $serializer): Response
    {
        $theme = new Theme();
        $theme->setName($request->get('name'));
        $this->themeRepository->persist($theme);

        $json = $serializer->serialize($theme, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['maps']]);

        return new Response($json, Response::HTTP_CREATED, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/admin/api/themes/{id}", options={"expose"=true}, name="admin_api_theme_edit", methods={"PUT"})
     */
    public function edit($id, Request $request, SerializerInterface $serializer): Response
    {
        $theme = $this->themeRepository->find($id);

        $theme->setName($request->get('name'));
        $this->themeRepository->persist($theme);;

        $json = $serializer->serialize($theme, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['maps']]);

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

}
