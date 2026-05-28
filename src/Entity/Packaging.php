<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * Warehouse shipping box (carton) available for packing orders.
 *
 * Named {@see Packaging} for compatibility with the project stub and DB table `packaging`.
 * In domain language and the public API this is a "box", not a package/shipment.
 */
#[Entity]
#[Table(name: 'packaging')]
class Packaging
{

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private ?int $id = null;

    public function __construct(
        #[Column(type: Types::FLOAT)]
        private float $width,
        #[Column(type: Types::FLOAT)]
        private float $height,
        #[Column(type: Types::FLOAT)]
        private float $length,
        #[Column(type: Types::FLOAT)]
        private float $maxWeight,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getLength(): float
    {
        return $this->length;
    }

    public function getMaxWeight(): float
    {
        return $this->maxWeight;
    }

    public function getVolume(): float
    {
        return $this->width * $this->height * $this->length;
    }

}
