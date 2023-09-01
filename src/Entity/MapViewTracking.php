<?php

namespace App\Entity;

use App\Repository\MapViewTrackingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MapViewTrackingRepository::class)
 */
class MapViewTracking
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $mapId;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $ip;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    public function getMapId(): ?int
    {
        return $this->mapId;
    }

    public function setMapId(int $mapId): self
    {
        $this->mapId = $mapId;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
