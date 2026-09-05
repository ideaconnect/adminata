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

namespace Adminata\Tests\App\DataFixtures;

use Adminata\Tests\App\Entity\Category;
use Adminata\Tests\App\Entity\Product;
use Adminata\Tests\App\Entity\ProductVariant;
use Adminata\Tests\App\Entity\Tag;
use Adminata\Tests\App\Enum\ProductStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Enough rows for a list to paginate, sort and filter, and deterministic: the visual-regression
 * runs of P1-10 diff screenshots of these pages, so nothing here may depend on the clock or on
 * random values.
 */
final class AppFixtures extends Fixture
{
    private const string EPOCH = '2026-01-05 09:00:00';

    /**
     * @var list<array{string, bool}>
     */
    private const array CATEGORIES = [
        ['Beverages', true],
        ['Snacks', true],
        ['Household', true],
        ['Discontinued', false],
    ];

    /**
     * @var list<string>
     */
    private const array TAGS = ['New', 'Sale', 'Organic', 'Bulk'];

    public function load(ObjectManager $manager): void
    {
        $epoch = new \DateTimeImmutable(self::EPOCH);
        $categories = [];

        foreach (self::CATEGORIES as $index => [$name, $active]) {
            $category = new Category();
            $category->setName($name);
            $category->setActive($active);
            $category->setDescription(\sprintf('Everything filed under %s.', strtolower($name)));
            $category->setCreatedAt($epoch->modify(\sprintf('+%d days', $index)));

            $manager->persist($category);
            $categories[] = $category;
        }

        $tags = [];

        foreach (self::TAGS as $name) {
            $tag = new Tag();
            $tag->setName($name);

            $manager->persist($tag);
            $tags[] = $tag;
        }

        $statuses = ProductStatus::cases();

        for ($i = 1; $i <= 42; ++$i) {
            $product = new Product();
            $product->setName(\sprintf('Product %02d', $i));
            $product->setSku(\sprintf('SKU-%04d', $i));
            $product->setPrice($i * 250);
            $product->setStatus($statuses[$i % \count($statuses)]);
            $product->setFeatured(0 === $i % 5);
            $product->setCategory($categories[$i % \count($categories)]);
            $product->setReleasedAt(0 === $i % 3 ? null : $epoch->modify(\sprintf('+%d days', $i)));
            $product->setAvailableFrom($epoch->modify(\sprintf('+%d days', $i * 2)));
            $product->setPickupAt(new \DateTimeImmutable(\sprintf('1970-01-01 %02d:%02d:00', 6 + $i % 12, 15 * ($i % 4))));
            $product->setStock(0 === $i % 7 ? 0 : 100 - $i);
            $product->setDescription(\sprintf(
                'Product %02d is stocked all year and ships from the central warehouse within one working day.',
                $i
            ));
            $product->setHighlights(\sprintf('<strong>Pick of the week</strong> — number %d', $i));
            $product->setSpecification([
                'weight' => \sprintf('%d g', 100 + $i * 5),
                'origin' => 0 === $i % 2 ? 'Poland' : 'Portugal',
            ]);

            // Deterministic, and every product carries at least one: a many-to-many cell with
            // nothing in it says nothing about how the cell renders.
            $product->addTag($tags[$i % \count($tags)]);

            if (0 === $i % 3) {
                $product->addTag($tags[($i + 1) % \count($tags)]);
            }

            foreach (['Small', 'Large'] as $offset => $label) {
                $variant = new ProductVariant();
                $variant->setLabel($label);
                $variant->setStock($i * 2 + $offset);

                $product->addVariant($variant);
                $manager->persist($variant);
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
