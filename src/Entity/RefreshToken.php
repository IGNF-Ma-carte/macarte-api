<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseEntity;

/**
 * classe étendue uniquement pour customiser le repository
 * @ORM\Table(name="refresh_tokens")
 * @ORM\Entity(repositoryClass=RefreshTokenRepository::class)
 */
class RefreshToken extends BaseEntity
{

}
