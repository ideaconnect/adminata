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

use Rector\Config\RectorConfig;
use Rector\Php70\Rector\FunctionLike\ExceptionHandlerTypehintRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\InlineStubPropertyToCreateStubMethodCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\NarrowUnusedSetUpDefinedPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\RemoveNeverUsedMockPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\PHPUnit120\Rector\Class_\PropertyCreateMockToCreateStubRector;
use Rector\PHPUnit\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;

/*
 * The rule set of idct/sonata-admin-mongodb-bundle, tracking adminata's PHP 8.4 floor. Rector 2.6
 * no longer ships versioned PHPUnit sets (PHPUNIT_100 … PHPUNIT_120); PHPUNIT_CODE_QUALITY plus
 * the composer-based set cover the same ground against the installed PHPUnit 13.
 *
 * Run it one package at a time so the mechanical diffs stay separate and upstream cherry-picks
 * still rebase: `make rector-fix` does that.
 */

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/packages/admin-bundle/src',
        __DIR__.'/packages/admin-bundle/tests',
        __DIR__.'/packages/block-bundle/src',
        __DIR__.'/packages/block-bundle/tests',
        __DIR__.'/packages/doctrine-extensions/src',
        __DIR__.'/packages/doctrine-extensions/tests',
        __DIR__.'/packages/doctrine-orm-admin-bundle/src',
        __DIR__.'/packages/doctrine-orm-admin-bundle/tests',
        __DIR__.'/packages/exporter/src',
        __DIR__.'/packages/exporter/tests',
        __DIR__.'/packages/form-extensions/src',
        __DIR__.'/packages/form-extensions/tests',
        __DIR__.'/packages/twig-extensions/src',
        __DIR__.'/packages/twig-extensions/tests',
        __DIR__.'/tests',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
    $rectorConfig->skip([
        // Skipped upstream and in the MongoDB fork, for the same reasons.
        ExceptionHandlerTypehintRector::class,
        // We apply `readonly` explicitly where it is safe; applying it everywhere collides with
        // the `readonly` + `__clone` pattern PHPStan's bleeding edge complains about.
        ReadOnlyPropertyRector::class,
        PreferPHPUnitThisCallRector::class,
        NarrowUnusedSetUpDefinedPropertyRector::class,
        RemoveNeverUsedMockPropertyRector::class,

        // Mock-to-stub rewrites that change what the inherited suites actually assert. Each was
        // caught by a failing test after a first run over the seven packages:
        //   * InlineStubPropertyToCreateStubMethodCallRector inlines a mock held in a property
        //     into every use, so `assertSame($this->admin, $event->getAdmin())` ends up comparing
        //     two freshly created stubs (ConfigureEventTest, ConfigureQueryEventTest,
        //     PersistenceEventTest).
        //   * PropertyCreateMockToCreateStubRector and CreateStubOverCreateMockArgRector turn
        //     mocks into stubs that no longer record invocations, and Sonata's tests set
        //     expectations on them later (CRUDControllerTest, ModelToIdPropertyTransformerTest).
        //   * DecorateWillReturnMapWithExpectsMockRector adds invocation expectations that the
        //     tested code does not meet (BreadcrumbsBuilderTest, AdminHelperTest).
        //   * ExpressionCreateMockToCreateStubRector cannot see expectations set by a helper the
        //     mock is handed to, so it downgrades mocks that are still verified
        //     (CRUDControllerTest::testBatchActionDeleteWithModelManagerException).
        InlineStubPropertyToCreateStubMethodCallRector::class,
        PropertyCreateMockToCreateStubRector::class,
        CreateStubOverCreateMockArgRector::class,
        DecorateWillReturnMapWithExpectsMockRector::class,
        ExpressionCreateMockToCreateStubRector::class,
    ]);
};
