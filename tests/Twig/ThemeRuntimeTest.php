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

namespace IDCT\Adminata\Tests\Twig;

use IDCT\Adminata\Twig\ThemeRuntime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ThemeRuntimeTest extends TestCase
{
    public function testTheThemeFallsBackToTheConfiguredModeWithoutARequest(): void
    {
        $runtime = new ThemeRuntime(new RequestStack(), ThemeRuntime::DARK);

        static::assertSame('dark', $runtime->getTheme());
    }

    public function testTheThemeFallsBackToTheConfiguredModeWithoutACookie(): void
    {
        $runtime = new ThemeRuntime($this->requestStack(), ThemeRuntime::LIGHT);

        static::assertSame('light', $runtime->getTheme());
    }

    #[DataProvider('provideTheCookieWinsCases')]
    public function testTheCookieWins(string $cookie): void
    {
        $runtime = new ThemeRuntime($this->requestStack([ThemeRuntime::COOKIE => $cookie]), ThemeRuntime::LIGHT);

        static::assertSame($cookie, $runtime->getTheme());
    }

    /**
     * @return iterable<array-key, array{string}>
     */
    public static function provideTheCookieWinsCases(): iterable
    {
        yield ['light'];
        yield ['dark'];
        yield ['system'];
    }

    #[DataProvider('provideAnUnknownCookieValueIsIgnoredCases')]
    public function testAnUnknownCookieValueIsIgnored(string $cookie): void
    {
        $runtime = new ThemeRuntime($this->requestStack([ThemeRuntime::COOKIE => $cookie]), ThemeRuntime::DARK);

        static::assertSame('dark', $runtime->getTheme());
    }

    /**
     * @return iterable<array-key, array{string}>
     */
    public static function provideAnUnknownCookieValueIsIgnoredCases(): iterable
    {
        yield 'unknown mode' => ['sepia'];
        yield 'empty' => [''];
        yield 'wrong case' => ['DARK'];
        yield 'injection attempt' => ['dark" onload="alert(1)'];
    }

    public function testAnUnknownConfiguredModeFallsBackToSystem(): void
    {
        $runtime = new ThemeRuntime($this->requestStack(), 'sepia');

        static::assertSame('system', $runtime->getTheme());
    }

    #[DataProvider('provideTheHtmlDirOfALocaleCases')]
    public function testTheHtmlDirOfALocale(string $locale, string $expected): void
    {
        $runtime = new ThemeRuntime(new RequestStack());

        static::assertSame($expected, $runtime->getHtmlDir($locale));
    }

    /**
     * @return iterable<array-key, array{string, string}>
     */
    public static function provideTheHtmlDirOfALocaleCases(): iterable
    {
        yield ['en', 'ltr'];
        yield ['pl', 'ltr'];
        yield ['en_GB', 'ltr'];
        yield ['ar', 'rtl'];
        yield ['ar_EG', 'rtl'];
        yield ['fa', 'rtl'];
        yield ['he_IL', 'rtl'];
        yield ['ur-PK', 'rtl'];
        yield ['', 'ltr'];
    }

    public function testTheHtmlDirDefaultsToTheRequestLocale(): void
    {
        $request = Request::create('/');
        $request->setLocale('he');

        $stack = new RequestStack();
        $stack->push($request);

        static::assertSame('rtl', new ThemeRuntime($stack)->getHtmlDir());
    }

    public function testTheHtmlDirIsLeftToRightWithoutARequest(): void
    {
        static::assertSame('ltr', new ThemeRuntime(new RequestStack())->getHtmlDir());
    }

    /**
     * @param array<string, string> $cookies
     */
    private function requestStack(array $cookies = []): RequestStack
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/', 'GET', [], $cookies));

        return $stack;
    }
}
