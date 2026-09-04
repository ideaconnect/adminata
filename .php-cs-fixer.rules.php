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

/*
 * The rule set shared by `.php-cs-fixer.dist.php` (the seven forked packages, which keep the
 * upstream Sonata header) and `.php-cs-fixer.adminata.php` (adminata's own PHP, which carries the
 * combined header). It is the rule set of idct/sonata-admin-mongodb-bundle: the sonata-project
 * dev-kit set with the migration sets raised to the PHP 8.4 floor and the PHPUnit 11+ stack.
 *
 * @return array<string, array<string, mixed>|bool>
 */
return static fn (string $header): array => [
    '@PHP8x4Migration' => true,
    '@PHP8x4Migration:risky' => true,
    '@PHPUnit11x0Migration:risky' => true,
    '@PSR12' => true,
    '@PSR12:risky' => true,
    '@Symfony' => true,
    '@Symfony:risky' => true,

    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'declare_strict_types' => true,
    'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
    'header_comment' => ['header' => $header],
    'list_syntax' => ['syntax' => 'short'],
    'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
    // Renames `__wakeup()` to `__unserialize(array $data)` without moving the restore logic
    // over, which silently breaks classes that pair `__serialize()` with `__wakeup()` —
    // form-extensions' InlineConstraint is one, and its own test catches it.
    'modern_serialization_methods' => false,
    'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    'no_useless_else' => true,
    'no_useless_return' => true,
    'no_superfluous_elseif' => true,
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
    'nullable_type_declaration_for_default_null_value' => true,
    'ordered_class_elements' => true,
    'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],
    'phpdoc_order' => ['order' => ['var', 'param', 'throws', 'return', 'phpstan-var', 'psalm-var', 'phpstan-param', 'psalm-param', 'phpstan-return', 'psalm-return']],
    'phpdoc_separation' => ['groups' => [
        ['phpstan-template', 'phpstan-template-covariant', 'phpstan-extends', 'phpstan-implements', 'phpstan-var', 'psalm-var', 'phpstan-param', 'psalm-param', 'phpstan-return', 'psalm-return'],
        ['psalm-suppress', 'phpstan-ignore-next-line'],
        ['Assert\\*'],
    ]],
    'php_unit_data_provider_name' => true,
    'php_unit_data_provider_return_type' => true,
    'php_unit_strict' => true,
    'php_unit_test_case_static_method_calls' => true,
    'phpdoc_to_comment' => ['ignored_tags' => ['psalm-suppress', 'phpstan-var']],
    'static_lambda' => true,
    'single_line_throw' => false,
    'strict_comparison' => true,
    'strict_param' => true,
    'void_return' => false,
];
