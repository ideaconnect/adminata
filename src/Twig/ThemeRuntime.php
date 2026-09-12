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

namespace IDCT\Adminata\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Light and dark mode, and the text direction of the page.
 *
 * The mode is resolved server-side so the layout can stamp `class="dark"` on `<html>` in the
 * response itself: a client-side toggle would paint the wrong theme first.
 */
final class ThemeRuntime implements RuntimeExtensionInterface
{
    public const string COOKIE = 'adminata_theme';

    public const string LIGHT = 'light';

    public const string DARK = 'dark';

    public const string SYSTEM = 'system';

    /**
     * @var list<string>
     */
    private const array MODES = [self::LIGHT, self::DARK, self::SYSTEM];

    /**
     * Locales written right to left. Matched on the language subtag, so `ar_EG` counts.
     *
     * @var list<string>
     */
    private const array RTL_LANGUAGES = ['ar', 'fa', 'he', 'ur'];

    /**
     * @param string $defaultMode the configured `adminata.theme.mode`
     *
     * @internal This class should only be used through Twig
     */
    public function __construct(
        private RequestStack $requestStack,
        private string $defaultMode = self::SYSTEM,
    ) {
    }

    /**
     * The theme this response must be rendered in: the visitor's `adminata_theme` cookie when it
     * holds one of the three modes, the configured default otherwise.
     *
     * @phpstan-return 'light'|'dark'|'system'
     */
    public function getTheme(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $cookie = $request?->cookies->get(self::COOKIE);

        if (\is_string($cookie) && \in_array($cookie, self::MODES, true)) {
            /** @phpstan-var 'light'|'dark'|'system' $cookie */
            return $cookie;
        }

        if (\in_array($this->defaultMode, self::MODES, true)) {
            /** @phpstan-var 'light'|'dark'|'system' $defaultMode */
            $defaultMode = $this->defaultMode;

            return $defaultMode;
        }

        return self::SYSTEM;
    }

    /**
     * The `dir` attribute for `<html>`. Without a locale, the current request's is used.
     *
     * @phpstan-return 'ltr'|'rtl'
     */
    public function getHtmlDir(?string $locale = null): string
    {
        $locale ??= $this->requestStack->getCurrentRequest()?->getLocale() ?? '';
        $language = strtolower(substr(str_replace('-', '_', $locale), 0, 2));

        return \in_array($language, self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr';
    }
}
