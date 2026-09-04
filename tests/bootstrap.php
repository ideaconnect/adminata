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

use Doctrine\Deprecations\Deprecation;

/*
 * Merged from the seven identical dev-kit bootstraps the forked packages shipped
 * (packages/*\/tests/bootstrap.php), which are deleted.
 *
 * Fix encoding issues when running tests on a host with a different locale.
 */
setlocale(\LC_ALL, 'en_US.UTF-8');

require_once __DIR__.'/../vendor/autoload.php';

if (class_exists(Deprecation::class)) {
    Deprecation::enableWithTriggerError();
}
