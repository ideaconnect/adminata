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
 * The rename map of PLAN/v2/02, as the engine reads it: an ordered list of PCRE rules, each
 * applied over whole file contents (and, segment by segment, over paths). Order matters — a
 * specific rule must run before the general one that would otherwise eat its match.
 *
 * scope:
 *   'all'  — applies on adminata's own trees and on an application (the name is unambiguous)
 *   'tree' — applies on adminata's own trees only; on an application the known-name lists
 *            generated into known.txt replace it, so that the application's own sonata… names
 *            are reported rather than rewritten (PLAN/v2/02 §1)
 *
 * globs / not_globs restrict a rule to file names (matched against the basename when the glob
 * has no slash). The pattern is written without delimiters; the engine adds them and the `m`
 * flag. `ns()` builds a namespace rule that matches `\`, `\\` and `\\\\` alike and emits the
 * run it saw ($1), so one rule covers PHP source, JSON, YAML and PHPStan baselines.
 */

$ns = static fn (string $from, string $to): array => [
    'scope' => 'all',
    // preg_quote doubles every backslash; each doubled one becomes a group matching the run —
    // or the backslash's URL encoding, which a functional test's query string carries.
    'pattern' => str_replace('\\\\', '(\\\\+|%5C)', preg_quote($from, '~')).'(?![A-Za-z0-9_])',
    'replacement' => str_replace('\\', '${1}', $to),
];

$word = '(?![A-Za-z0-9_])';

return [
    // --- Namespaces (N2, N19; the merged trees' upstream namespaces for applications coming
    //     straight from sonata-project/*) --------------------------------------------------
    $ns('Sonata\AdminBundle', 'IDCT\Adminata'),
    $ns('Sonata\DoctrineORMAdminBundle', 'IDCT\Adminata\DoctrineORM'),
    $ns('Sonata\DoctrineMongoDBAdminBundle', 'IDCT\Adminata\DoctrineMongoDB'),
    $ns('Sonata\BlockBundle', 'IDCT\Adminata'),
    $ns('Sonata\Form', 'IDCT\Adminata\Form'),
    $ns('Sonata\Twig', 'IDCT\Adminata\Twig'),
    $ns('Sonata\Exporter', 'IDCT\Adminata\Exporter'),
    $ns('Sonata\Doctrine', 'IDCT\Adminata\Doctrine'),

    // --- The storage layers' classes and Twig namespaces (N19) -----------------------------
    ['scope' => 'all', 'pattern' => 'SonataDoctrineORMAdminBundle', 'replacement' => 'AdminataDoctrineORMBundle'],
    ['scope' => 'all', 'pattern' => 'SonataDoctrineORMAdminExtension', 'replacement' => 'AdminataDoctrineORMExtension'],
    ['scope' => 'all', 'pattern' => 'SonataDoctrineMongoDBAdminBundle', 'replacement' => 'AdminataDoctrineMongoDBBundle'],
    ['scope' => 'all', 'pattern' => 'SonataDoctrineMongoDBAdminExtension', 'replacement' => 'AdminataDoctrineMongoDBExtension'],
    ['scope' => 'all', 'pattern' => '@(!?)SonataDoctrineORMAdmin'.$word, 'replacement' => '@${1}AdminataDoctrineORM'],
    ['scope' => 'all', 'pattern' => '@(!?)SonataDoctrineMongoDBAdmin'.$word, 'replacement' => '@${1}AdminataDoctrineMongoDB'],

    // --- One Twig namespace; the three 1.0 aliases fold into it (N8) ----------------------
    ['scope' => 'all', 'pattern' => '@(!?)Sonata(?:Block|Form|Twig)'.$word, 'replacement' => '@${1}Adminata'],
    ['scope' => 'all', 'pattern' => '@(!?)SonataAdmin'.$word, 'replacement' => '@${1}Adminata'],

    // --- PHPStan type aliases that would otherwise collide with the AdminataConfiguration
    //     class (review log of PLAN/v2/README.md) ------------------------------------------
    ['scope' => 'tree', 'pattern' => 'SonataAdminConfigurationOptions'.$word, 'replacement' => 'AdminataConfigOptions'],
    ['scope' => 'tree', 'pattern' => 'SonataAdminConfiguration'.$word, 'replacement' => 'AdminataConfig'],

    // --- Explicit lower-case names, before the families they belong to --------------------
    ['scope' => 'all', 'pattern' => 'sonata\.admin\.twig\.sonata_admin_(extension|runtime)', 'replacement' => 'adminata.admin.twig.adminata_${1}'],
    ['scope' => 'tree', 'pattern' => 'recipe_sonata_admin_without_user_bundle', 'replacement' => 'recipe_adminata_without_user_bundle'],
    ['scope' => 'all', 'pattern' => '\bsonata_doctrine_orm_admin'.$word, 'replacement' => 'adminata_doctrine_orm'],
    ['scope' => 'all', 'pattern' => '\bsonata_doctrine_mongo_db_admin'.$word, 'replacement' => 'adminata_doctrine_mongodb'],
    ['scope' => 'all', 'pattern' => '\bsonata_admin_(dashboard|search|redirect|retrieve_form_element|append_form_element|short_object_information|set_object_field_value|retrieve_autocomplete_items)'.$word, 'replacement' => 'adminata_${1}'],
    ['scope' => 'all', 'pattern' => '\bsonata_admin_sidebar'.$word, 'replacement' => 'adminata_sidebar'],
    ['scope' => 'all', 'pattern' => '\bsonata_admin_content', 'replacement' => 'adminata_content'],
    ['scope' => 'all', 'pattern' => '\bsonata:admin:', 'replacement' => 'adminata:'],
    ['scope' => 'all', 'pattern' => 'debug:sonata:block', 'replacement' => 'debug:adminata:block'],
    ['scope' => 'all', 'pattern' => 'make:sonata:admin', 'replacement' => 'make:adminata:admin'],
    ['scope' => 'all', 'pattern' => 'sonataadmin', 'replacement' => 'adminata'],
    ['scope' => 'tree', 'pattern' => '_sonata_(?=[a-z])', 'replacement' => '_adminata_'],

    // --- The `sonata_admin` root and the other places the bare token names the bundle
    //     rather than the admin of a field (PLAN/v2/02 §3.1) -------------------------------
    ['scope' => 'all', 'pattern' => "TreeBuilder\\('sonata_admin'\\)", 'replacement' => "TreeBuilder('adminata')"],
    ['scope' => 'all', 'pattern' => "(getExtensionConfig|loadFromExtension|prependExtensionConfig|hasExtension)\\('sonata_admin'", 'replacement' => '${1}(\'adminata\''],
    ['scope' => 'all', 'pattern' => "(DEFAULT_PACKAGE|ROUTE_TYPE_NAME) = 'sonata_admin'", 'replacement' => '${1} = \'adminata\''],
    ['scope' => 'all', 'pattern' => "('package'|'package_name'|'alias') => 'sonata_admin'", 'replacement' => '${1} => \'adminata\''],
    ['scope' => 'all', 'pattern' => ", 'sonata_admin'\\)", 'replacement' => ", 'adminata')"],
    ['scope' => 'all', 'pattern' => "return 'sonata_admin';", 'replacement' => "return 'adminata';"],
    ['scope' => 'all', 'pattern' => '^(\s*)sonata_admin:', 'replacement' => '${1}adminata:', 'globs' => ['*.yaml', '*.yml', '*.rst', '*.md']],
    ['scope' => 'all', 'pattern' => 'sonata_admin\.(?=[a-z_])', 'replacement' => 'adminata.', 'not_globs' => ['*.twig']],
    ['scope' => 'all', 'pattern' => '\bsonata_admin'.$word, 'replacement' => 'adminata', 'globs' => ['*.yaml', '*.yml', '*.rst', '*.md']],
    ['scope' => 'tree', 'pattern' => "'sonata_admin'", 'replacement' => "'adminata'", 'globs' => ['ConfigContractTest.php']],

    // --- The families (N6–N13); in application mode known.txt takes over from here --------
    ['scope' => 'tree', 'pattern' => 'sonata_type_', 'replacement' => 'adminata_type_'],
    ['scope' => 'tree', 'pattern' => 'sonata_flash_', 'replacement' => 'adminata_flash_'],
    ['scope' => 'all', 'pattern' => 'sonata-ba-', 'replacement' => 'adminata-'],
    ['scope' => 'tree', 'pattern' => 'sonata-(?!project|admin-mongodb-bundle|doctrine-extensions)', 'replacement' => 'adminata-'],
    ['scope' => 'tree', 'pattern' => 'SonataAdmin(?![a-z])', 'replacement' => 'Adminata'],
    ['scope' => 'tree', 'pattern' => 'Sonata(?=[A-Z])', 'replacement' => 'Adminata'],
    ['scope' => 'tree', 'pattern' => '\bsonata_admin'.$word, 'replacement' => 'adminata_admin'],
    ['scope' => 'tree', 'pattern' => '(?<![A-Za-z0-9])sonata_', 'replacement' => 'adminata_'],
    ['scope' => 'tree', 'pattern' => '(?<![A-Za-z0-9])sonata\.(?=[a-z])', 'replacement' => 'adminata.'],
    // The default of `security.role_admin` and the roles the docs derive from example admin codes;
    // on an application a ROLE_* is data, never rewritten (UPGRADE.md §6.3).
    ['scope' => 'tree', 'pattern' => '\bROLE_SONATA_', 'replacement' => 'ROLE_ADMINATA_'],
    // The routes cache directory.
    ['scope' => 'tree', 'pattern' => "'/sonata/admin'", 'replacement' => "'/adminata/admin'"],
    ['scope' => 'tree', 'pattern' => '(?<![A-Za-z0-9_])sonata(?=[A-Z])', 'replacement' => 'adminata'],
];
