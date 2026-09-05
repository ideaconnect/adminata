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

// `getenv()` and not only `$_SERVER`: PHP's built-in server does not put the process environment
// into `$_SERVER`, and that is how the browser suites ask for the test environment
// (Adminata\Tests\Support\DemoServer).
$environment = $_SERVER['APP_ENV'] ?? getenv('APP_ENV');

if (!is_string($environment) || '' === $environment) {
    $environment = 'dev';
}

$kernel = new Kernel($environment, 'prod' !== $environment);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
