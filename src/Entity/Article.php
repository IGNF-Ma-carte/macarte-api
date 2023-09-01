<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="article_v4")
 * @ORM\Entity(repositoryClass=ArticleRepository::class)
 */
class Article
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVE = 'archived';

    static public function getStatuses(){
        return [
            'Brouillon' => self::STATUS_DRAFT,
            'Publié' => self::STATUS_PUBLISHED,
            'Archivé' => self::STATUS_ARCHIVE,
        ];
    }

    /* **************** */
    /* Les categories destinées à la FAQ doivent être préfixées par "faq_" et le texte de getCategories par "FAQ - " */
    /* *******************/
    const CATEGORY_HOME_TITLE = 'home_title'; // configuration du titre de la page accueil
    const CATEGORY_HOME_BUTTON = 'home_button'; // boutons dans le titre de page accueil
    const CATEGORY_NEWS = 'news'; // sur page accueil
    const CATEGORY_ADVANTAGE_1 = 'advantage_1'; // sur page accueil
    const CATEGORY_ADVANTAGE_2 = 'advantage_2'; // sur page accueil
    const CATEGORY_TESTIMONY = 'testimony'; // témoignage sur page accueil
    const CATEGORY_CGU = 'cgu'; // sur page /cgu
    const CATEGORY_MENTION = 'mention'; // sur page /mentions-legales
    const CATEGORY_COOKIE = 'cookie'; // sur page /cookies-et-statistiques
    const CATEGORY_TUTO = 'tuto'; // dans la partie tuto
    const CATEGORY_VERSION = 'version';
    const CATEGORY_FAQ_ATLAS = 'faq_atlas'; 
    const CATEGORY_FAQ_MACARTE = 'faq_macarte';  
    const CATEGORY_FAQ_STORYMAP = 'faq_storymap'; 
    const CATEGORY_FAQ_STATISTIC = 'faq_statistic';
    const CATEGORY_FAQ_MESADRESSES = 'faq_mesadresses';
    const CATEGORY_FAQ_MONCOMPTE = 'faq_moncompte';
    const CATEGORY_EDUGEO_TITLE = 'edugeo_title'; //titre de la page accueil-edugeo
    const CATEGORY_EDUGEO_BUTTON = 'edugeo_button'; // boutons sous le titre de la page accueil-edugeo
    const CATEGORY_EDUGEO_1 = 'edugeo_1'; //articles de la page accueil de la page accueil-edugeo (partie blanche largeur complete)
    const CATEGORY_EDUGEO_2 = 'edugeo_2'; //articles de la page accueil de la page accueil-edugeo (partie grise 3 colonnes)

    public static function getCategories(){
        // dans l'ordre d'apparition des catégories sur les templates ou renvoyées par l'API
        return array(
            'Accueil - Titre' => self::CATEGORY_HOME_TITLE,
            'Accueil - Boutons titre' => self::CATEGORY_HOME_BUTTON,
            'Accueil - Avantages' => self::CATEGORY_ADVANTAGE_1,
            'Accueil - Avantages bleus' => self::CATEGORY_ADVANTAGE_2,
            'Accueil - Actualités' => self::CATEGORY_NEWS,
            'Accueil - Témoignages' => self::CATEGORY_TESTIMONY,
            'CGU' => self::CATEGORY_CGU,
            'Cookies et statistiques' => self::CATEGORY_COOKIE,
            'FAQ - Ma carte' => self::CATEGORY_FAQ_MACARTE,
            'FAQ - Ma carte narrative' => self::CATEGORY_FAQ_STORYMAP,
            'FAQ - Ma carte statistique' => self::CATEGORY_FAQ_STATISTIC,
            'FAQ - Mes adresses' => self::CATEGORY_FAQ_MESADRESSES,
            'FAQ - Atlas/partage' => self::CATEGORY_FAQ_ATLAS,
            'FAQ - Mon compte' => self::CATEGORY_FAQ_MONCOMPTE,
            'Mentions légales' => self::CATEGORY_MENTION,
            'Tutoriel' => self::CATEGORY_TUTO,
            'Version' => self::CATEGORY_VERSION,
            'EDUGEO - Titre' => self::CATEGORY_EDUGEO_TITLE,
            'EDUGEO - BOUTONS' => self::CATEGORY_EDUGEO_BUTTON,
            'EDUGEO 1re partie' => self::CATEGORY_EDUGEO_1,
            'EDUGEO 2e partie' => self::CATEGORY_EDUGEO_2,
        );
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $category;

    /**
     * @ORM\Column(type="boolean")
     */
    private $visible;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="articles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $tags = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imgUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $linkText;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $linkUrl;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->visible = true;
        $this->position = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getImgUrl(): ?string
    {
        return $this->imgUrl;
    }

    public function setImgUrl(?string $imgUrl): self
    {
        $this->imgUrl = $imgUrl;

        return $this;
    }

    public function getLinkText(): ?string
    {
        return $this->linkText;
    }

    public function setLinkText(?string $linkText): self
    {
        $this->linkText = $linkText;

        return $this;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(?string $linkUrl): self
    {
        $this->linkUrl = $linkUrl;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
