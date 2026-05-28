<?php declare(strict_types = 1);

namespace App\Repository;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Entity\PackingCache;
use App\Service\PackingCacheKeyGenerator;
use Doctrine\ORM\EntityManager;

class PackingCacheRepository
{

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly PackingCacheKeyGenerator $cacheKeyGenerator,
    )
    {
    }

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function findForInput(
        array $products,
        array $boxes,
    ): ?PackingCache
    {
        return $this->findByInputHash($this->cacheKeyGenerator->generate($products, $boxes));
    }

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function saveForInput(
        array $products,
        array $boxes,
        ?int $packagingId,
    ): void
    {
        $this->saveResult($this->cacheKeyGenerator->generate($products, $boxes), $packagingId);
    }

    public function findByInputHash(string $inputHash): ?PackingCache
    {
        return $this->entityManager->getRepository(PackingCache::class)->findOneBy([
            'inputHash' => $inputHash,
        ]);
    }

    public function saveResult(
        string $inputHash,
        ?int $packagingId,
    ): void
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
