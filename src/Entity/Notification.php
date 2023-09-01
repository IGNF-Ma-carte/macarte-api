<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 */
class Notification
{
    const TYPE_DANGER = 'danger';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';

    public static function getTypes(){
        return [
            'Alerte' => self::TYPE_DANGER,
            'Succès' => self::TYPE_SUCCESS,
            'Avertissement' => self::TYPE_WARNING,
            'Info' => self::TYPE_INFO,
        ];
    }

    const SCOPE_MACARTE = 'macarte';
    const SCOPE_MESADRESSES = 'mesadresses';
    const SCOPE_STATISTIC = 'statistic';
    const SCOPE_STORYMAP = 'storymap';
    const SCOPE_EDUGEO = 'edugeo';
    const SCOPE_GEOPORTAIL = 'geoportail';
    

    public static function getScopes(){
        return [
            'Ma Carte' => self::SCOPE_MACARTE,
            'Mes adresses' => self::SCOPE_MESADRESSES,
            'Carte statistique' => self::SCOPE_STATISTIC,
            'Carte narrative' => self::SCOPE_STORYMAP,
            'Edugeo' => self::SCOPE_STORYMAP,
            'Geoportail' => self::SCOPE_STORYMAP,
        ];
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $scope;

    /**
     * @ORM\Column(type="date")
     */
    private $showFrom;

    /**
     * @ORM\Column(type="date")
     */
    private $showUntil;

    /**
     * @ORM\Column(type="integer")
     */
    private $repeatability;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notifications")
     * @ORM\JoinColumn(nullable=false)
     */
    private $updatedBy;

    public function __construct()
    {
        $this->repeatability = 1;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getShowFrom(): ?\DateTimeInterface
    {
        return $this->showFrom;
    }

    public function setShowFrom(\DateTimeInterface $showFrom): self
    {
        $this->showFrom = $showFrom;

        return $this;
    }

    public function getShowUntil(): ?\DateTimeInterface
    {
        return $this->showUntil;
    }

    public function setShowUntil(\DateTimeInterface $showUntil): self
    {
        $this->showUntil = $showUntil;

        return $this;
    }

    public function getRepeatability(): ?int
    {
        return $this->repeatability;
    }

    public function setRepeatability(int $repeatability): self
    {
        $this->repeatability = $repeatability;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
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
}
