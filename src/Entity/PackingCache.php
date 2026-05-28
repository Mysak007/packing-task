<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Entity]
#[Table(name: 'packing_cache')]
#[UniqueConstraint(name: 'uniq_input_hash', columns: ['input_hash'])]
class PackingCache
{

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private ?int $id = null;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        #[Column(type: Types::STRING, length: 64)]
        private string $inputHash,
        #[Column(type: Types::INTEGER, nullable: true)]
        private ?int $packagingId,
    )
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getPackagingId(): ?int
    {
        return $this->packagingId;
    }

    public function setPackagingId(?int $packagingId): void
    {
        $this->packagingId = $packagingId;
        $this->createdAt = new DateTimeImmutable();
    }

}
