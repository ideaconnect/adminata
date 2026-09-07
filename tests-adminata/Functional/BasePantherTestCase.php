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

namespace Adminata\Tests\Functional;

use Adminata\Tests\App\EventListener\BrowserConsoleRecorderListener;
use Adminata\Tests\Support\DemoServer;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

/**
 * A real browser against the demo application (PLAN/08 §3).
 *
 * Same switch as idct/sonata-admin-mongodb-bundle: with `PANTHER_SELENIUM_HOST` set, talk to a
 * running Selenium — `docker compose up -d selenium` starts one — instead of spawning a local
 * geckodriver. `PANTHER_FIREFOX_PORT` moves the spawned geckodriver off the default 4444 when
 * something else on the machine already listens there.
 *
 * The application is served by {@see DemoServer} rather than by Panther. Panther manages one web
 * server per process, and while the doctrine-orm-admin-bundle suite lived in this repository it
 * claimed that server first; the suite has a repository of its own now, but every request here
 * still goes through {@see url()} and is absolute, which costs nothing and keeps these tests
 * indifferent to whatever else a process has started.
 */
abstract class BasePantherTestCase extends PantherTestCase
{
    protected Client $client;
    /**
     * One browser session for the whole class.
     *
     * `createPantherClient()` reuses its client for Chrome and Firefox but not for Selenium: it
     * overwrites `self::$pantherClients[0]` and the old session is never quit, so a class with
     * five tests opens five sessions and the fourth waits out its timeout against a node
     * configured for one.
     */
    private static ?Client $session = null;

    public static function tearDownAfterClass(): void
    {
        // `PantherTestCase::tearDownAfterClass()` quits `self::$pantherClients`, this one among
        // them; forgetting it here is what keeps the next class from reusing a dead session.
        self::$session = null;

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        if (null !== self::$session) {
            $this->client = self::$session;

            // Nothing a previous test chose may reach the next. The session goes with the rest:
            // `persist_filters` keeps a submitted filter in it, so a test that filtered a list
            // would otherwise decide what the next one sees. Signing in again is the price.
            $this->client->getWebDriver()->manage()->deleteAllCookies();
            $this->signIn();

            return;
        }

        $options = [
            'external_base_uri' => DemoServer::baseUri(),
            'connection_timeout_in_ms' => 5000,
            'request_timeout_in_ms' => 60000,
        ];

        $seleniumHost = self::stringFromServer('PANTHER_SELENIUM_HOST');

        if (null !== $seleniumHost) {
            $this->client = self::$session = static::createPantherClient(
                ['browser' => PantherTestCase::SELENIUM] + $options,
                [],
                ['host' => $seleniumHost, 'capabilities' => self::capabilities()],
            );

            $this->signIn();

            return;
        }

        $port = self::stringFromServer('PANTHER_FIREFOX_PORT');

        // No `capabilities` on this route. Panther applies them with setCapability(), which
        // replaces the whole `moz:firefoxOptions` it has just built — `--headless` included — so
        // Firefox would try to open a window on a runner that has no display and exit at once.
        // The one thing adminata wanted from it, `ui.prefersReducedMotion`, Panther 2.4 sets by
        // itself; the Selenium route below still needs it spelled out.
        $this->client = self::$session = static::createPantherClient(
            ['browser' => PantherTestCase::FIREFOX] + $options,
            [],
            null !== $port ? ['port' => (int) $port] : [],
        );

        $this->signIn();
    }

    /** The absolute address of a demo page. */
    protected function url(string $path): string
    {
        return DemoServer::baseUri().$path;
    }

    /**
     * Everything the page wrote to the console, in order.
     *
     * `BrowserConsoleRecorderListener` puts the recorder first in the `<head>` of every response
     * the demo sends in the test environment, because geckodriver implements no log endpoint of
     * its own.
     *
     * @return list<string>
     */
    protected function consoleMessages(): array
    {
        $messages = $this->client->executeScript(
            \sprintf('return window.%s ?? null;', BrowserConsoleRecorderListener::PROPERTY)
        );

        static::assertIsArray(
            $messages,
            'The console recorder was not installed. The demo has to be served in the test '
            .'environment for it to be there — see Adminata\Tests\Support\DemoServer.'
        );

        return array_values(array_map(strval(...), $messages));
    }

    /**
     * @param string $message what the browser was doing, for the failure line
     */
    protected function assertConsoleIsEmpty(string $message = ''): void
    {
        static::assertSame(
            [],
            $this->consoleMessages(),
            '' !== $message ? $message : 'The page wrote to the browser console.'
        );
    }

    /**
     * Signs the browser session in through the demo's login form, once.
     *
     * Not `http_basic`: the only way to hand credentials to a browser through a URL is
     * `http://user:pass@host`, and Firefox puts a confirmation dialog in front of repeating one —
     * a modal that blocks WebDriver until it times out, a minute per test. A form leaves a session
     * cookie, which every later navigation carries by itself.
     */
    private function signIn(): void
    {
        $crawler = $this->client->request('GET', $this->url('/login'));

        $this->client->submit($crawler->selectButton('Sign in')->form([
            '_username' => 'admin',
            '_password' => 'admin',
        ]));
    }

    /**
     * `ui.prefersReducedMotion` is Panther's own default, restated because passing
     * `moz:firefoxOptions` replaces it wholesale rather than merging into it.
     *
     * @return array<string, mixed>
     */
    private static function firefoxOptions(): array
    {
        return ['prefs' => ['ui.prefersReducedMotion' => 1]];
    }

    private static function capabilities(): DesiredCapabilities
    {
        $capabilities = DesiredCapabilities::firefox();
        $capabilities->setCapability('moz:firefoxOptions', self::firefoxOptions());

        return $capabilities;
    }

    private static function stringFromServer(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? null;

        return \is_string($value) && '' !== $value ? $value : null;
    }
}
