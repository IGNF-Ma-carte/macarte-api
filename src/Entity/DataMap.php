<?php

namespace App\Entity;

use App\Repository\DataMapRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DataMapRepository::class)
 * @ORM\Table(name="map_data")
 */
class DataMap
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="json")
     */
    private $data;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(object $data): self
    {
        $this->data = $data;

        return $this;
    }
}
