<?php

namespace App\Controller\Api;

use stdClass;
use App\Entity\Article;
use OpenApi\Annotations as OA;
use App\Repository\ArticleRepository;
use App\Controller\Api\ApiAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiEditorialController extends ApiAbstractController
{
    private $articleRepository;
    
    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    /**
     * @Route("/api/editorial/followers", name="api_editorial_followers", methods={"GET"})
     * 
     * @OA\Get(
     *      tags={"Editorial"},
     *      path="/api/editorial/followers",
     *      @OA\Response(
     *          response="200",
     *          description="Liste des followers par réseau social",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="facebook", 
     *                  type="string", 
     *                  example="65K",
     *              ),
     *              @OA\Property(
     *                  property="twitter", 
     *                  type="string", 
     *                  example="13K",
     *              ),
     *              @OA\Property(
     *                  property="linkedin", 
     *                  type="string", 
     *                  example="24K",
     *              ),
     *              @OA\Property(
     *                  property="instagram", 
     *                  type="string", 
     *                  example="11K",
     *              ),
     *          )
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")
     * )
     */
    public function followers(): Response
    {
        $file = $this->getParameter('kernel.project_dir')
            .DIRECTORY_SEPARATOR.'public'
            .DIRECTORY_SEPARATOR.'bundles'
            .DIRECTORY_SEPARATOR.'igncharte'
            .DIRECTORY_SEPARATOR.'json'
            .DIRECTORY_SEPARATOR.'followers.json'
        ;
        if(!is_file($file)){
            return $this->returnResponse('file not found', Response::HTTP_NOT_FOUND);
        }

        return new Response(file_get_contents($file), Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/api/editorial/megamenu", name="api_editorial_megamenu", methods={"GET"})
     *
     * @OA\Get(
     *      tags={"Editorial"},
     *      path="/api/editorial/megamenu",
     *      @OA\Response(
     *          response="200",
     *          description="Contenu html du megamenu",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="html", 
     *                  type="string", 
     *              ),
     *          ),
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")
     * )
     */
    public function megamenu(): Response
    {
        $file = $this->getParameter('kernel.project_dir')
            .DIRECTORY_SEPARATOR.'templates'
            .DIRECTORY_SEPARATOR.'generated'
            .DIRECTORY_SEPARATOR.'megamenu.html'
        ;
        if(!is_file($file)){
            return $this->returnResponse('file not found', Response::HTTP_NOT_FOUND);
        }
        $response = new stdClass();
        $response->html = file_get_contents($file);
        return new JsonResponse($response, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/api/editorial/categories", name="api_editorial_categories", methods={"GET"})
     * 
     * @OA\Get(
     *      tags={"Editorial"},
     *      path="/api/editorial/categories",
     *      @OA\Response(
     *          response="200",
     *          description="Liste des categories des articles de l'editorial",
     *          @OA\JsonContent(
     *              type="array", 
     *              @OA\Items(
     *                  @OA\Property(property="key", type="string", description="clé de la catégorie"),
     *                  @OA\Property(property="value", type="string", description="Nomde la catégorie")
     *              )
     *          ),
     *      ),
     * )
     */
    public function getCategories(): Response
    {
        $categories = Article::getCategories();

        $array = [];
        foreach($categories as $value => $key){
            if(str_starts_with($key, 'faq_')){
                $value = str_replace('FAQ - ',  '', $value);
            }
            $category = new stdClass();
            $category->key = $key;
            $category->value = $value;

            $array[] = $category;
        }
        return new JsonResponse($array, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @Route("/api/editorial/articles/{category}", name="api_editorial_article", methods={"GET"})
     * 
     * @OA\Get(
     *      tags={"Editorial"},
     *      path="/api/editorial/articles/{category}",
     *      @OA\Parameter(
     *          name="category",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Liste des articles de la categorie",
     *          @OA\JsonContent(
     *              type="array", 
     *              @OA\Items(ref="#/components/schemas/Article")
     *          )
     *      ),
     *      @OA\Response(response="404", ref="#/components/responses/NotFound")
     * )
     */
    public function getArticles($category, SerializerInterface $serializer): Response
    {
        if(!in_array($category, array_values(Article::getCategories()))){
            return $this->returnResponse('Category not found', Response::HTTP_NOT_FOUND);
        }

        $articles = $this->articleRepository->findBy(
            [
                'category' => $category,
                'status' => Article::STATUS_PUBLISHED,
            ], 
            ['position' => 'ASC']
        );

        $json = $serializer->serialize($articles, 'json', []);

        return new Response($json, Response::HTTP_OK, array(
            'Content-Type' => 'application/json',
        ));
    }
}
