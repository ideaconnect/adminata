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

namespace Adminata\Rename;

/**
 * Rewrites every Sonata-owned identifier to its IDCT\Adminata name (PLAN/v2/02).
 *
 * The engine has no autoloader on purpose: apply.php runs from an application's vendor/ directory,
 * where adminata's autoload-dev is not installed, so it requires this file directly.
 *
 * Two modes. In TREE mode every rule of rules.php applies — on adminata's own trees every sonata…
 * token is ours, fixtures and documentation samples included. In APP mode only the rules marked
 * `all` apply, and the families they leave out (service ids, form types, hooks, …) are covered by
 * the exact names generated into known.txt; whatever else still says sonata is the application's
 * own name, which is reported and left alone.
 *
 * @phpstan-type Rule array{scope: string, pattern: string, replacement: string, globs?: list<string>, not_globs?: list<string>}
 * @phpstan-type Known array{family: string, token: string, kind: string}
 * @phpstan-type Allow array{skip: list<string>, keep: list<string>, retired: list<string>}
 * @phpstan-type Leftover array{line: int, text: string, retired: bool}
 */
final class Engine
{
    public const string MODE_TREE = 'tree';
    public const string MODE_APP = 'app';

    private const string PLACEHOLDER = "\0K%d\0";
    private const string PLACEHOLDER_PATTERN = '~\0K(\d+)\0~';

    /** @var list<array{pattern: string, replacement: string, globs: list<string>, not_globs: list<string>}> */
    private array $active = [];

    /** @var list<array{pattern: string, replacement: string, globs: list<string>, not_globs: list<string>}> */
    private array $treeRules = [];

    /** @var list<array{pattern: string, translate: array<string, string>}> */
    private array $knownRules = [];

    /** @var list<string> */
    private array $keep;

    /** @var list<string> */
    private array $retired;

    /** @var list<string> */
    private array $skip;

    /**
     * @param list<Rule>  $rules
     * @param Allow       $allow
     * @param list<Known> $known
     */
    public function __construct(array $rules, array $allow, array $known, private string $mode)
    {
        if (!\in_array($mode, [self::MODE_TREE, self::MODE_APP], true)) {
            throw new \InvalidArgumentException(\sprintf('Unknown mode "%s".', $mode));
        }

        $this->skip = $allow['skip'];
        $this->keep = $allow['keep'];
        $this->retired = $allow['retired'];

        foreach ($rules as $rule) {
            $compiled = [
                'pattern' => '~'.$rule['pattern'].'~m',
                'replacement' => $rule['replacement'],
                'globs' => $rule['globs'] ?? [],
                'not_globs' => $rule['not_globs'] ?? [],
            ];
            $this->treeRules[] = $compiled;

            if (self::MODE_TREE === $mode || 'all' === $rule['scope']) {
                $this->active[] = $compiled;
            }
        }

        if (self::MODE_APP === $mode) {
            $this->knownRules = $this->compileKnown($known);
        }
    }

    public static function fromDirectory(string $directory, string $mode): self
    {
        /** @var list<Rule> $rules */
        $rules = require $directory.'/rules.php';
        /** @var Allow $allow */
        $allow = require $directory.'/allow.php';

        return new self($rules, $allow, self::readKnown($directory.'/known.txt'), $mode);
    }

    /**
     * @return list<Known>
     */
    public static function readKnown(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $known = [];
        $lines = file($file, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);

        foreach (false === $lines ? [] : $lines as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }

            $columns = explode("\t", $line);

            if (3 !== \count($columns)) {
                throw new \RuntimeException(\sprintf('Malformed line in %s: "%s".', $file, $line));
            }

            $known[] = ['family' => $columns[0], 'token' => $columns[1], 'kind' => $columns[2]];
        }

        return $known;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    /**
     * Whether a path is one the engine never touches and the gate never scans.
     */
    public function isSkipped(string $path): bool
    {
        return array_any($this->skip, static fn ($glob) => fnmatch($glob, $path) || (!str_contains($glob, '/') && fnmatch($glob, basename($path))));
    }

    /**
     * Rewrites file content. `$path` is the file's path relative to the tree root; it selects the
     * glob-restricted rules. A binary file (one holding a NUL byte) comes back untouched.
     */
    public function rewrite(string $text, string $path): string
    {
        if (str_contains($text, "\0")) {
            return $text;
        }

        [$protected, $originals] = $this->protect($text);
        $protected = $this->applyRules($protected, $path);

        return $this->restore($protected, $originals);
    }

    /**
     * The renamed path, or the same path when nothing in it is a name of ours.
     */
    public function rewritePath(string $path): string
    {
        if ($this->isSkipped($path)) {
            return $path;
        }

        $segments = explode('/', $path);
        $renamed = [];

        foreach ($segments as $segment) {
            $renamed[] = $this->rewrite($segment, $path);
        }

        return implode('/', $renamed);
    }

    /**
     * What the gate reports for one file: the lines the engine would still change (a rewrite that
     * has not happened), the lines that still carry a sonata… token no rule knows (tree mode: the
     * file is not done; application mode: the application's own names, listed for its author),
     * and, in application mode, the lines naming one of the five retired bundles.
     *
     * @return list<Leftover>
     */
    public function leftovers(string $text, string $path): array
    {
        if (str_contains($text, "\0")) {
            return [];
        }

        $found = [];
        $rewritten = $this->rewrite($text, $path);

        if ($rewritten !== $text) {
            $before = explode("\n", $text);
            $after = explode("\n", $rewritten);

            foreach ($before as $index => $line) {
                if (($after[$index] ?? null) !== $line) {
                    $found[$index + 1] = ['line' => $index + 1, 'text' => trim($line), 'retired' => false];
                }
            }
        }

        [$protected] = $this->protect($rewritten, self::MODE_APP === $this->mode);
        $visible = explode("\n", $rewritten);

        foreach (explode("\n", $protected) as $index => $line) {
            if (isset($found[$index + 1]) || 1 !== preg_match('~sonata~i', $line)) {
                continue;
            }

            $retired = false;

            if (self::MODE_APP === $this->mode) {
                foreach ($this->retired as $phrase) {
                    if (1 === preg_match('~'.$phrase.'~', $line)) {
                        $retired = true;

                        break;
                    }
                }
            }

            $found[$index + 1] = ['line' => $index + 1, 'text' => trim($visible[$index] ?? $line), 'retired' => $retired];
        }

        ksort($found);

        return array_values($found);
    }

    /**
     * The new name of one token, as the tree rules spell it — what the exact-name rules of
     * application mode replace a known token with.
     */
    public function translate(string $token): string
    {
        $text = $token;

        foreach ($this->treeRules as $rule) {
            if ([] !== $rule['globs'] || [] !== $rule['not_globs']) {
                continue;
            }

            $text = $this->replace($rule['pattern'], $rule['replacement'], $text);
        }

        return $text;
    }

    /**
     * Hides the phrases that stay: each match becomes a placeholder no rule can match, and the
     * originals come back through restore(). With $exposeRetired the five retired bundle names
     * are left visible, so that the gate can report them.
     *
     * @return array{string, array<int, string>}
     */
    private function protect(string $text, bool $exposeRetired = false): array
    {
        $originals = [];
        $phrases = $exposeRetired ? $this->keep : [...$this->retired, ...$this->keep];

        foreach ($phrases as $phrase) {
            $text = (string) preg_replace_callback('~'.$phrase.'~', static function (array $match) use (&$originals): string {
                $originals[] = $match[0];

                return \sprintf(self::PLACEHOLDER, \count($originals) - 1);
            }, $text);
        }

        return [$text, $originals];
    }

    /**
     * @param array<int, string> $originals
     */
    private function restore(string $text, array $originals): string
    {
        return (string) preg_replace_callback(self::PLACEHOLDER_PATTERN, static fn (array $match): string => $originals[(int) $match[1]] ?? '', $text);
    }

    private function applyRules(string $text, string $path): string
    {
        foreach ($this->active as $rule) {
            if (!$this->applies($rule['globs'], $rule['not_globs'], $path)) {
                continue;
            }

            $text = $this->replace($rule['pattern'], $rule['replacement'], $text);
        }

        foreach ($this->knownRules as $rule) {
            $text = (string) preg_replace_callback($rule['pattern'], static fn (array $match): string => $rule['translate'][$match[0]] ?? $match[0], $text);
        }

        return $text;
    }

    private function replace(string $pattern, string $replacement, string $text): string
    {
        $result = preg_replace($pattern, $replacement, $text);

        if (null === $result) {
            throw new \RuntimeException(\sprintf('Rule %s failed: %s', $pattern, preg_last_error_msg()));
        }

        return $result;
    }

    /**
     * @param list<string> $globs
     * @param list<string> $notGlobs
     */
    private function applies(array $globs, array $notGlobs, string $path): bool
    {
        $name = basename($path);

        foreach ($notGlobs as $glob) {
            if (fnmatch($glob, $name)) {
                return false;
            }
        }

        if ([] === $globs) {
            return true;
        }

        return array_any($globs, static fn ($glob) => fnmatch($glob, $name));
    }

    /**
     * One alternation per family, longest token first, each token translated once by the tree
     * rules. Prefix tokens (written with a trailing separator in the sources: `sonata-ba-field-`,
     * `sonata.admin.manipulator.acl.object.`) and the Stimulus identifiers match without an end
     * boundary, so that everything Stimulus derives from an identifier follows it.
     *
     * @param list<Known> $known
     *
     * @return list<array{pattern: string, translate: array<string, string>}>
     */
    private function compileKnown(array $known): array
    {
        $boundaries = [
            'class' => ['(?<![A-Za-z0-9_])', '(?![A-Za-z0-9_])'],
            'id' => ['(?<![A-Za-z0-9_.])', '(?![A-Za-z0-9_.])'],
            'underscore' => ['(?<![A-Za-z0-9])', '(?![A-Za-z0-9_])'],
            'hyphen' => ['(?<![A-Za-z0-9])', '(?![A-Za-z0-9-])'],
            'camel' => ['(?<![A-Za-z0-9_])', '(?![A-Za-z0-9_])'],
        ];

        $families = [];

        foreach ($known as $entry) {
            if (!isset($boundaries[$entry['family']])) {
                throw new \RuntimeException(\sprintf('Unknown family "%s" in known.txt.', $entry['family']));
            }

            $families[$entry['family']][$entry['token']] = $entry['kind'];
        }

        $compiled = [];

        foreach ($families as $family => $tokens) {
            uksort($tokens, static function (string $a, string $b): int {
                $byLength = \strlen($b) <=> \strlen($a);

                return 0 !== $byLength ? $byLength : strcmp($a, $b);
            });

            $exact = [];
            $prefix = [];
            $translate = [];

            foreach ($tokens as $token => $kind) {
                $translate[$token] = $this->translate($token);
                if ('prefix' === $kind) {
                    $prefix[] = preg_quote($token, '~');
                } else {
                    $exact[] = preg_quote($token, '~');
                }
            }

            [$before, $after] = $boundaries[$family];

            if ([] !== $prefix) {
                $compiled[] = ['pattern' => '~'.$before.'('.implode('|', $prefix).')~', 'translate' => $translate];
            }

            if ([] !== $exact) {
                $compiled[] = ['pattern' => '~'.$before.'('.implode('|', $exact).')'.$after.'~', 'translate' => $translate];
            }
        }

        return $compiled;
    }
}
