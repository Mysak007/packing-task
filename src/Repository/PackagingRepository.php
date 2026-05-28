<?php

namespace App\Repository;

use App\Entity\Packaging;
use Doctrine\ORM\EntityManager;

class PackagingRepository
{
    public function __construct(private readonly EntityManager $entityManager)
    {
    }

    /**
     * @return list<Packaging>
     */
    public function findAll(): array
    {
        /** @var list<Packaging> $packaging */
        $packaging = $this->entityManager->getRepository(Packaging::class)->findAll();

        usort(
            $packaging,
            static fn (Packaging $left, Packaging $right): int => $left->getVolume() <=> $right->getVolume()
        );

        return $packaging;
    }

    public function findById(int $id): ?Packaging
    {
        $packaging = $this->entityManager->getRepository(Packaging::class)->find($id);

        if (!$packaging instanceof Packaging) {
            return null;
        }

        return $packaging;
    }
}
