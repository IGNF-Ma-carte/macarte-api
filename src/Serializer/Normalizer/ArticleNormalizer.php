<?php

namespace App\Serializer\Normalizer;

use App\Entity\Article;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @OA\Schema(
 *      schema="Article",
 *      @OA\Property(
 *          type="integer", 
 *          property="id", 
 *          description="Identifiant de l'article",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="category", 
 *          description="Categorie de l'article",
 *      ),
 *      @OA\Property(
 *          type="number", 
 *          property="position", 
 *          description="Ordre d'affichage de l'article",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="title", 
 *          description="Titre de l'article",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="content", 
 *          description="Contenu de l'article",
 *      ),
 *      @OA\Property(
 *          type="array", 
 *          property="tags", 
 *          description="Tags de l'article",
 *          @OA\Items(type="string"),
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="updated_at", 
 *          description="Date de mise à jour de l'article",
 *          format="date-time",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="updated_by", 
 *          description="Nom public de l'utilisateur qui a modifié l'article",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="img_url", 
 *          description="Url de l'image d'illustration de l'article",
 *          format="uri",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="link_text", 
 *          description="Article de la catégorie news : texte du lien",
 *      ),
 *      @OA\Property(
 *          type="string", 
 *          property="link_url", 
 *          description="Article de la catégorie news : destination du lien",
 *          format="uri",
 *      ),
 * )
 */
class ArticleNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($article, string $format = null, array $context = []): array
    {
        /** @var Article $article */
        $data["id"] = $article->getId();
        $data["category"] = $article->getCategory();
        $data["position"] = $article->getPosition();
        $data["title"] = $article->getTitle();
        $data["content"] = $article->getContent();
        $data["tags"] = $article->getTags();
        $data["img_url"] = $article->getImgUrl();
        $data["link_text"] = $article->getLinkText();
        $data["link_url"] = $article->getLinkUrl();
        // $data['created_at'] = $article->getCreatedAt()->format(\DateTimeInterface::W3C);
        $data['updated_at'] = $article->getUpdatedAt()->format(\DateTimeInterface::W3C);
        $data['updated_by'] = $article->getUpdatedBy()->getPublicName();

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof \App\Entity\Article;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
