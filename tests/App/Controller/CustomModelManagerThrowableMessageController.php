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

namespace IDCT\Adminata\Tests\App\Controller;

use IDCT\Adminata\Controller\CRUDController;
use IDCT\Adminata\Exception\ModelManagerThrowable;

/**
 * @phpstan-extends CRUDController<object>
 */
final class CustomModelManagerThrowableMessageController extends CRUDController
{
    public const string ERROR_MESSAGE = 'message from model manager throwable';

    /**
     * @phpstan-throws void
     */
    protected function handleModelManagerThrowable(ModelManagerThrowable $exception): string
    {
        return self::ERROR_MESSAGE;
    }
}
