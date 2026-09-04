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

use Sonata\DoctrineORMAdminBundle\Tests\App\AppKernel;
use Symfony\Component\HttpFoundation\Request;

// Running from this package: vendor/ is three levels up. Running from adminata, where the
// package lives in packages/doctrine-orm-admin-bundle, it is five.
foreach ([__DIR__.'/../../../vendor/autoload.php', __DIR__.'/../../../../../vendor/autoload.php'] as $autoload) {
    if (file_exists($autoload)) {
        require $autoload;

        break;
    }
}

$kernel = new AppKernel('test', false);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
