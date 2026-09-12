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

namespace IDCT\Adminata\Tests\Exception\Block\Renderer;

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\Exception\Block\Renderer\MonkeyThrowRenderer;
use IDCT\Adminata\Model\BlockInterface;

/**
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
final class MonkeyThrowRendererTest extends TestCase
{
    public function testRenderWithStandardException(): void
    {
        $this->expectException(\Exception::class);

        $exception = new \Exception();
        $renderer = new MonkeyThrowRenderer();

        $renderer->render($exception, $this->createMock(BlockInterface::class));
    }

    public function testRenderWithRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $exception = new \RuntimeException();
        $renderer = new MonkeyThrowRenderer();

        $renderer->render($exception, $this->createMock(BlockInterface::class));
    }
}
