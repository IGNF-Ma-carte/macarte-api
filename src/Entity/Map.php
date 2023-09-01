<?php

namespace App\Entity;

use App\Repository\MapRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MapRepository::class)
 */
class Map
{
    const SHARE_PRIVATE = 'private';
    const SHARE_NO_ATLAS = 'no-atlas';
    const SHARE_ATLAS = 'atlas';
    const SHARE_MODEL = 'model';

    /**
     * @return array
     */
    public static function getShares(){
        return array(
            self::SHARE_PRIVATE,  //n'est visible que par moi ou ceux qui connaissent idView
            // self::SHARE_NO_ATLAS,  //n'est visible que par ceux qui connaissent idView
            self::SHARE_ATLAS,  //est visible en public
            // self::SHARE_MODEL,  //peut etre forkée
        );
    }

    const PREMIUM_DEFAULT = 'default';
    const PREMIUM_EDUGEO = 'edugeo';

    /**
     * @return array
     */
    public static function getPremiums(){
        return array(
            self::PREMIUM_DEFAULT,
            self::PREMIUM_EDUGEO
        );
    }

    const TYPE_MACARTE = 'macarte';
    const TYPE_MESADRESSES = 'mesadresses';
    const TYPE_STATISTIC = 'statistic';
    const TYPE_STORYMAP = 'storymap';

    /**
     * @return array
     */
    public static function getTypes(){
        return array(
            self::TYPE_MACARTE,
            self::TYPE_MESADRESSES,
            self::TYPE_STATISTIC,
            self::TYPE_STORYMAP,
        );
    }


    /* ------------------ IDENTIFIANTS --------------------- */ 
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="id_unique", type="string", length=255)
     * @var string
     */
    private $idEdit;

    /**
     * @ORM\Column(name="id_unique_iframe", type="string", length=255)
     * @var string
     */
    private $idView;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dataFile;


    /* ------------------ PRESENTATION PUBLIQUE --------------------- */ 

    /**
     * @ORM\Column(name="titre", type="string", length=255, nullable=true)
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     * @var string|null
     */
    private $description;
    
    /**
     * @ORM\Column(name="nb_view", type="integer")
     * @var int
     */
    private $nbView;

    /**
     * @ORM\Column(name="img_url", type="string", length=255)
     * @var string
     */
    private $imgUrl;
  

    /* ------------------ RECHERCHE --------------------- */ 

    /**
     * @ORM\Column(name="share", type="string", length=255)
     * @var string
     */
    private $share;

    /**
     * @ORM\Column(name="premium", type="string", length=255)
     * @var string
     */
    private $premium;
    
    /**
     * @ORM\Column(name="type", type="string", length=255)
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(name="active", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="valide", type="boolean")
     * @var bool
     */
    private $valid;

    
    /* ------------------ DATES --------------------- */ 
    /**
     * @ORM\Column(name="date", type="datetime")
     * @var \DateTimeInterface
     */
    private $createdAt;

    /**
     * @ORM\Column(name="maj", type="datetime")
     * @var \DateTimeInterface
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="invalidated_at", type="datetime", nullable=true)
     * @var \DateTimeInterface
     */
    private $invalidatedAt;


    /* ------------------ EMPRISE --------------------- */ 
    /**
     * @ORM\Column(name="lonMin", type="float", nullable=true)
     */
    private $lonMin;

    /**
     * @ORM\Column(name="lonMax", type="float", nullable=true)
     */
    private $lonMax;

    /**
     * @ORM\Column(name="latMin", type="float", nullable=true)
     */
    private $latMin;

    /**
     * @ORM\Column(name="latMax", type="float", nullable=true)
     */
    private $latMax;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $zoom;

    /* ------------------ RELATIONS --------------------- */ 
    /**
     * @ORM\ManyToOne(targetEntity=Theme::class, inversedBy="maps")
     */
    private $theme;

    /**
     * @ORM\OneToOne(targetEntity=DataMap::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="datamap_id", nullable=false)
     */
    private $data;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="maps")
     * @ORM\JoinColumn(nullable=false)
     */
    private $creator;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $editor;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $filesize;


    public function __construct()
    {
        $this->nbView = 0;
        $this->createdAt = new \DateTime();
        $this->updatedAt = $this->getCreatedAt();
        $this->valid = true;
        $this->active = true;
        $this->premium = 'default';
        $this->share = 'private';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getInvalidatedAt(): ?\DateTimeInterface
    {
        return $this->invalidatedAt;
    }

    public function setInvalidatedAt(?\DateTimeInterface $invalidatedAt): self
    {
        $this->invalidatedAt = $invalidatedAt;

        return $this;
    }

    public function getIdView(): ?string
    {
        return $this->idView;
    }

    public function setIdView(string $idView): self
    {
        $this->idView = $idView;

        return $this;
    }

    public function getIdEdit(): ?string
    {
        return $this->idEdit;
    }

    public function setIdEdit(string $idEdit): self
    {
        $this->idEdit = $idEdit;

        return $this;
    }

    public function getLonMin(): ?float
    {
        return $this->lonMin;
    }

    public function setLonMin(?float $lonMin): self
    {
        $this->lonMin = $lonMin;

        return $this;
    }

    public function getLonMax(): ?float
    {
        return $this->lonMax;
    }

    public function setLonMax(?float $lonMax): self
    {
        $this->lonMax = $lonMax;

        return $this;
    }

    public function getLatMin(): ?float
    {
        return $this->latMin;
    }

    public function setLatMin(?float $latMin): self
    {
        $this->latMin = $latMin;

        return $this;
    }

    public function getLatMax(): ?float
    {
        return $this->latMax;
    }

    public function setLatMax(?float $latMax): self
    {
        $this->latMax = $latMax;

        return $this;
    }

    public function getZoom(): ?int
    {
        return $this->zoom;
    }

    public function setZoom(?int $zoom): self
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function getNbView(): ?int
    {
        return $this->nbView;
    }

    public function setNbView(int $nbView): self
    {
        $this->nbView = $nbView;

        return $this;
    }

    public function getImgUrl(): ?string
    {
        return $this->imgUrl;
    }

    public function setImgUrl(string $imgUrl): self
    {
        $this->imgUrl = $imgUrl;

        return $this;
    }

    public function getPremium(): ?string
    {
        return $this->premium;
    }

    public function setPremium(?string $premium): self
    {
        $this->premium = $premium;

        return $this;
    }

    public function getShare(): ?string
    {
        return $this->share;
    }

    public function setShare(string $share): self
    {
        $this->share = $share;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getDataMap(): ?DataMap
    {
        return $this->data;
    }

    public function setDataMap(?DataMap $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getEditor(): ?User
    {
        return $this->editor;
    }

    public function setEditor(?User $editor): self
    {
        $this->editor = $editor;

        return $this;
    }

    public function getDataFile(): ?string
    {
        return $this->dataFile;
    }

    public function setDataFile(?string $dataFile): self
    {
        $this->dataFile = $dataFile;

        return $this;
    }

    public function getFilesize(): ?int
    {
        return $this->filesize;
    }

    public function setFilesize(?int $filesize): self
    {
        $this->filesize = $filesize;

        return $this;
    }
}
