<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'packing_cache')]
#[ORM\UniqueConstraint(name: 'uniq_input_hash', columns: ['input_hash'])]
class PackingCache
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $inputHash;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $packagingId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(string $inputHash, ?int $packagingId)
    {
        $this->inputHash = $inputHash;
        $this->packagingId = $packagingId;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInputHash(): string
    {
        return $this->inputHash;
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
