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

namespace IDCT\Adminata\Exporter\Writer;

use IDCT\Adminata\Exporter\Exception\AdminataExporterException;

interface WriterInterface
{
    /**
     * @throws AdminataExporterException
     */
    public function open(): void;

    /**
     * @param mixed[] $data
     *
     * @throws AdminataExporterException
     */
    public function write(array $data): void;

    public function close(): void;
}
