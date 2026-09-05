<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The entry type of the native Symfony CollectionType on ProductAdmin: an inline sub-form with
 * `allow_add` and `allow_delete`, which is what the `sonata-collection` controller drives.
 */
#[ORM\Entity]
#[ORM\Table(name: 'demo_product_variant')]
class ProductVariant implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $label = '';

    #[ORM\Column(type: Types::INTEGER)]
    private int $stock = 0;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function __toString(): string
    {
        return '' !== $this->label ? $this->label : 'Variant';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Doctrine assigns the identifier by reflection, so nothing in this application writes it;
     * the setter is here for the same reason the inherited test entities have one, to give the
     * property an assignment PHPStan can see (`Tests\App\Entity\Base` upstream).
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }
}
