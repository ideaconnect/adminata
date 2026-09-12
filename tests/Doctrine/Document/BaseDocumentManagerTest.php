<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IDCT\Adminata\Tests\Doctrine\Document;

use Doctrine\Persistence\ManagerRegistry;
use IDCT\Adminata\Doctrine\Document\BaseDocumentManager;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-extends BaseDocumentManager<object>
 */
final class DocumentManager extends BaseDocumentManager
{
}

final class BaseDocumentManagerTest extends TestCase
{
    public function getManager(): DocumentManager
    {
        return new DocumentManager(\stdClass::class, $this->createMock(ManagerRegistry::class));
    }

    public function test(): void
    {
        static::assertSame(\stdClass::class, $this->getManager()->getClass());
    }
}
