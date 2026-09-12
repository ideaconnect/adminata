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
 * What the engine leaves alone (PLAN/v2/02 §5).
 *
 * skip:    files the engine never rewrites and the gate never scans — history, provenance and
 *          the upgrade documents, where the old names are meant to appear (globs against the
 *          path relative to the tree root).
 * keep:    phrases that are never rewritten and never reported: the vendor and the people
 *          (attribution), the other Sonata bundles' names that the inherited cookbook pages
 *          mention, and two repository and file names that keep the word.
 * retired: the five bundle names 1.0 deleted. Never rewritten (there is nothing to rewrite
 *          them to); reported on an application, where a line naming one is a line to delete.
 */
return [
    'skip' => [
        'LICENSE',
        'NOTICE',
        'src/Resources/meta/LICENSE',
        'UPSTREAM.md',
        'CHANGELOG.md',
        'CHANGELOG-sonata.md',
        'changelog/*',
        'MIGRATION.md',
        'UPGRADE.md',
        'UPGRADE-*.md',
        'docs/upgrading.rst',
        'PLAN/*',
        'PROJECT_PLAN.md',
        'upstream/remotes.txt',
        'upstream/merged.txt',
        'upstream/exclude/*',
        'upstream/rename/*',
        'tests-adminata/Unit/Rename/*',
        'composer.lock',
        'symfony.lock',
        'package-lock.json',
        'yarn.lock',
        '*.cache',
    ],
    'keep' => [
        'sonata-project/Sonata[A-Za-z0-9]+',
        'sonata-project',
        'sonata-admin-mongodb-bundle',
        'sonata-doctrine-extensions',
        'CHANGELOG-sonata',
        'sonata:user:',
        '\bSonata(?![A-Za-z0-9_\\\\]|%5C)',
        'Sonata(?:User|Page|Media|Seo|Intl|News|Classification|Timeline|Notification|Cache|Formatter|Comment|Workflow|Demo|Post|Xxx|EasyExtends|Dashboard|Article|Product|Order|Customer|Basket|Payment|Delivery|Invoice|Price|Translation)(?:Bundle)?\b',
        'Sonata\\\\+(?!AdminBundle\b|DoctrineORMAdminBundle\b|DoctrineMongoDBAdminBundle\b|BlockBundle\b|Form\b|Twig\b|Exporter\b|Doctrine\b)[A-Za-z]+',
        // Their configuration roots and a few of their names the inherited pages and tests use as
        // they are: SonataUserBundle's flash types and login blocks, and what its `Post` entity
        // derives (`admin_sonata_news_post`, `/sonata/news/post`).
        '(?<![A-Za-z0-9])sonata_(?:user|page|media|news|seo|intl|classification|timeline|notification|cache|formatter|comment)\\b',
        '(?<![A-Za-z0-9])sonata_(?:user|page)_bundle\\b',
        '(?<![A-Za-z0-9])sonata_user_(?:login_[a-z_]+|success|error)\\b',
        '(?<![A-Za-z0-9])sonata_news_post\\b',
        '/sonata/news/post',
        // Real services of other Sonata bundles that the inherited pages and tests mention. The
        // example admin codes beside them (sonata.news.admin.post, sonata.user.admin.group) are
        // examples, and follow the rename like every other example.
        '\bsonata\.(?:page\.block\.container|page\.cms\.page|media\.pool|user\.manager\.user|user\.security\.user_provider|comment\.block\.discus)\b',
    ],
    'retired' => [
        'Sonata(?:Block|Doctrine|Exporter|Form|Twig)Bundle\b',
    ],
];
