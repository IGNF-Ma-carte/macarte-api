<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/editorial")
 */
class AdminArticleController extends AbstractController
{
    private $articleRepository;
    
    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    /**
     * @Route("/", name="admin_article_index")
     */
    public function index(Request $request): Response
    {
        $articles = [];
        foreach(Article::getCategories() as $name => $key){
            $articles[$key] = $this->articleRepository->findBy(['category' => $key], ['position' => 'ASC']);
        }
        
        return $this->render('admin/article/index.html.twig', [
            'categoriesName' => Article::getCategories(),
            'articles' => $articles,
            'currentCategory' => $request->get('category')
        ]);
    }

    /**
     * @Route("/ajouter", name="admin_article_add")
     */
    public function add(Request $request): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setUpdatedBy($this->getUser());
            $this->articleRepository->persist($article);
            $this->articleRepository->moveToFirstPosition($article, true);

            return $this->redirectToRoute('admin_article_index', ['category' => $article->getCategory()]);
        }

        return $this->render('admin/article/add_edit.html.twig', [
            'form' => $form->createView(),
            'category' => $request->get('category')
        ]);
    }

    /**
     * @Route("/modifier/{id}", name="admin_article_edit")
     */
    public function edit($id, Request $request): Response
    {   
        $article = $this->articleRepository->find($id);
        if(!$article){
            $this->addFlash('danger', "L'article n'existe pas");
            return $this->redirectToRoute('admin_article_index');
        }

        /** @var Article $originalArticle */
        $originalArticle = clone($article);
        
        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setTags(array_filter($article->getTags()));
            if($originalArticle->getCategory() != $article->getCategory()){
                $this->articleRepository->updatePositionsForNewCategory($article, $originalArticle->getCategory());
            }
            $article->setUpdatedBy($this->getUser());
            $article->setUpdatedAt(new \DateTimeImmutable());
            $this->articleRepository->persist($article);

            return $this->redirectToRoute('admin_article_index', ['category' => $article->getCategory()]);
        }
        return $this->render('/admin/article/add_edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    /**
     * @Route("/voir/{id}", name="admin_article_view")
     */
    public function view($id): Response
    {
        $article = $this->articleRepository->find($id);

        if(!$article){
            $this->addFlash('danger', "L'article n'existe pas");
            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('/admin/article/view.html.twig', [
            'article' => $article
        ]);
    }

    /**
     * @Route("/supprimer/{id}", name="admin_article_remove")
     */
    public function remove($id): Response
    {
        $article = $this->articleRepository->find($id);
        $category = $article->getCategory();
        if(!$article){
            $this->addFlash('danger', "L'article n'existe pas");
            return $this->redirectToRoute('admin_article_index');
        }
        $this->articleRepository->remove($article, true);

        return $this->redirectToRoute('admin_article_index', ['category' => $category]);
    }

    /**
     * Monte l'article d'un cran dans la liste (diminue la position de 1)
     *
     * @Route("/remonter/{id}", name="admin_article_move_up")
     */
    public function moveUpAction($id)
    {
        $article = $this->articleRepository->find($id);

        if(!$article){
            return;
        }
        $previousArticle = $this->articleRepository->findPrevious($article);

        if (!is_null($previousArticle)) {
            $previousArticle->setPosition($previousArticle->getPosition() + 1);
            $article->setPosition($article->getPosition() - 1);

            $this->articleRepository->persist($previousArticle, true);
            $this->articleRepository->persist($article, true);
        }

        return $this->redirectToRoute('admin_article_index', ['category' => $article->getCategory()]);
    }

    /**
     * Descend l'article d'un cran dans la liste
     *
     * @Route("/descendre/{id}", name="admin_article_move_down")
     */
    public function moveDownAction($id)
    {
        $article = $this->articleRepository->find($id);

        if(!$article){
            return;
        }
        $nextArticle = $this->articleRepository->findNext($article);

        if (!is_null($nextArticle)) {
            $nextArticle->setPosition($nextArticle->getPosition() - 1);
            $article->setPosition($article->getPosition() + 1);

            $this->articleRepository->persist($nextArticle, true);
            $this->articleRepository->persist($article, true);
        }

        return $this->redirectToRoute('admin_article_index', ['category' => $article->getCategory()]);
    }

    /**
     * @Route("/basculer/{id}", name="admin_article_toggle")
     */
    public function publish($id): Response
    {
        $article = $this->articleRepository->find($id);

        if(!$article){
            $this->addFlash('danger', "L'article n'existe pas");
            return $this->redirectToRoute('admin_article_index');
        }
        if($article->getCategory()  != Article::CATEGORY_NEWS ){
            $this->addFlash('danger', "Seuls les articles de la catégorie Actualités peuvent être masqués");
            return $this->redirectToRoute('admin_article_index');
        }

        $article->setVisible(!$article->isVisible());
        $this->articleRepository->persist($article, true);

        return $this->redirectToRoute('admin_article_index', ['category' => $article->getCategory()]);
    }

    /**
     * @Route("/status/{id}/{status}", name="admin_article_status")
     */
    public function changeStatus($id, $status): Response
    {
        $article = $this->articleRepository->find($id);
        $article->setStatus($status);
        $this->articleRepository->persist($article, true);

        return $this->redirectToRoute('admin_article_index', ['category' => $article->getCategory()]);
    }
    
    /**
     * @Route("/images", name="admin_article_images")
     */
    public function images(): Response
    {
        return $this->render('/admin/article/images.html.twig', []);
    }

}
