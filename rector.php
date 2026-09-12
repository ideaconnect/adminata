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
use Rector\Php74\Rector\If_\IfToNullCoalescingAssignRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\InlineStubPropertyToCreateStubMethodCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\NarrowUnusedSetUpDefinedPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\RemoveNeverUsedMockPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\BareCreateMockAssignToDirectUseRector;
use Rector\PHPUnit\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\PHPUnit120\Rector\Class_\PropertyCreateMockToCreateStubRector;
use Rector\PHPUnit\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;

/*
 * adminata's rule set, tracking its PHP 8.4 floor. Rector 2.6 no longer ships versioned PHPUnit
 * sets (PHPUNIT_100 … PHPUNIT_120); PHPUNIT_CODE_QUALITY plus the composer-based set cover the
 * same ground against the installed PHPUnit 13.
 */

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/tests-adminata',
        __DIR__.'/upstream/rename',
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
        // caught by a failing test after a first run over the forked packages:
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
        //   * BareCreateMockAssignToDirectUseRector inlines a mock into its single use, which
        //     drops the `@var` docblock that gives the mocked interface its generic argument
        //     (DatagridMapperTest); PHPStan cannot infer that from `createMock()`.
        BareCreateMockAssignToDirectUseRector::class,

        // `$object ??= $adminObject;` cannot carry the `@phpstan-var T` that says which admin
        // produced the object, so keep the explicit `if` in this one method.
        IfToNullCoalescingAssignRector::class => [
            __DIR__.'/src/Controller/CRUDController.php',
        ],
    ]);
};
