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
 * The forked packages. Their files keep the upstream Sonata header so that upstream diffs still
 * apply; adminata's own PHP is covered by `.php-cs-fixer.adminata.php`, which carries the
 * combined header. `make lint` runs both.
 */

$header = <<<'HEADER'
    This file is part of the Sonata Project package.

    (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    HEADER;

$rules = (require __DIR__.'/.php-cs-fixer.rules.php')($header);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/packages')
    ->exclude('node_modules')
    ->exclude('Resources/skeleton')
    ->exclude('Resources/public')
    ->exclude('assets')
    ->exclude('docs')
    ->exclude('var')
    // Symfony writes this into a test application's config directory when its kernel boots. It is
    // generated, gitignored, and not ours to format.
    ->notPath('App/config/reference.php');

$config = new PhpCsFixer\Config();
$config
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.packages.cache')
    // The composer.json floor is PHP 8.4 while this machine and CI also run 8.5; say so
    // explicitly instead of printing the unsupported-PHP-version warning on every run.
    ->setUnsupportedPhpVersionAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());

return $config;
