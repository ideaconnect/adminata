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

use Adminata\Tests\App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$environment = $_SERVER['APP_ENV'] ?? 'dev';
$kernel = new Kernel(is_string($environment) ? $environment : 'dev', 'prod' !== $environment);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
