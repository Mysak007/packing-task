<?php

namespace App\Repository;

use App\Entity\PackingCache;
use Doctrine\ORM\EntityManager;

class PackingCacheRepository
{
    public function __construct(private readonly EntityManager $entityManager)
    {
    }

    public function findByInputHash(string $inputHash): ?PackingCache
    {
        $cache = $this->entityManager->getRepository(PackingCache::class)->findOneBy([
            'inputHash' => $inputHash,
        ]);

        if (!$cache instanceof PackingCache) {
            return null;
        }

        return $cache;
    }

    public function saveResult(string $inputHash, ?int $packagingId): void
    {
        $cache = $this->findByInputHash($inputHash);
        if ($cache instanceof PackingCache) {
            $cache->setPackagingId($packagingId);
        } else {
            $cache = new PackingCache($inputHash, $packagingId);
            $this->entityManager->persist($cache);
        }

        $this->entityManager->flush();
    }
}
