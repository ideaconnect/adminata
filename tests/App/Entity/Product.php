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

use Adminata\Tests\App\Enum\ProductStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'demo_product')]
class Product implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 32, unique: true)]
    private string $sku = '';

    /** Minor units, so that the integer list and filter field types have something honest to show. */
    #[ORM\Column(type: Types::INTEGER)]
    private int $price = 0;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: ProductStatus::class)]
    private ProductStatus $status = ProductStatus::Draft;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $featured = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $availableFrom = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $pickupAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Markup on purpose: `TYPE_HTML` renders it unescaped, and something has to prove it. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $highlights = null;

    /** @var array<string, string> */
    #[ORM\Column(type: Types::JSON)]
    private array $specification = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $stock = 0;

    /** Set by the demo's custom `archive` batch action. */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $archived = false;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'products')]
    #[ORM\JoinTable(name: 'demo_product_tag')]
    private Collection $tags;

    /** @var Collection<int, ProductVariant> */
    #[ORM\OneToMany(targetEntity: ProductVariant::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    private Collection $variants;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->variants = new ArrayCollection();
    }

    public function __toString(): string
    {
        return '' !== $this->name ? $this->name : 'Product';
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getStatus(): ProductStatus
    {
        return $this->status;
    }

    public function setStatus(ProductStatus $status): void
    {
        $this->status = $status;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): void
    {
        $this->releasedAt = $releasedAt;
    }

    public function getAvailableFrom(): ?\DateTimeImmutable
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(?\DateTimeImmutable $availableFrom): void
    {
        $this->availableFrom = $availableFrom;
    }

    public function getPickupAt(): ?\DateTimeImmutable
    {
        return $this->pickupAt;
    }

    public function setPickupAt(?\DateTimeImmutable $pickupAt): void
    {
        $this->pickupAt = $pickupAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getHighlights(): ?string
    {
        return $this->highlights;
    }

    public function setHighlights(?string $highlights): void
    {
        $this->highlights = $highlights;
    }

    /**
     * @return array<string, string>
     */
    public function getSpecification(): array
    {
        return $this->specification;
    }

    /**
     * @param array<string, string> $specification
     */
    public function setSpecification(array $specification): void
    {
        $this->specification = $specification;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): void
    {
        $this->archived = $archived;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        if ($this->tags->contains($tag)) {
            return;
        }

        $this->tags->add($tag);
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(ProductVariant $variant): void
    {
        if ($this->variants->contains($variant)) {
            return;
        }

        $this->variants->add($variant);
        $variant->setProduct($this);
    }

    public function removeVariant(ProductVariant $variant): void
    {
        if (!$this->variants->removeElement($variant)) {
            return;
        }

        $variant->setProduct(null);
    }
}
