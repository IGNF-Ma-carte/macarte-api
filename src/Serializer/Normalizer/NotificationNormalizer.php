<?php

namespace App\Serializer\Normalizer;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @OA\Schema(
 *      schema="Notification",
 *      description="Notifications",
 *      @OA\Property(type="integer", property="id"),
 *      @OA\Property(type="string", property="description"),
 *      @OA\Property(type="string", property="scope", description="Applicatifs où afficher la notification"),
 *      @OA\Property(type="string", property="showFrom", format="date-time"),
 *      @OA\Property(type="string", property="showUntil", format="date-time"),
 *      @OA\Property(type="integer", property="repeatibility", description="Nombre de fois à afficher la notification"),
 * )
 */
class NotificationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // TODO: add, edit, or delete some data

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof \App\Entity\Notification;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
