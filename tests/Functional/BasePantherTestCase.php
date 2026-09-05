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
 * The application is served by {@see DemoServer} rather than by Panther, because Panther manages
 * one web server per process and the doctrine-orm-admin-bundle suite has already claimed it.
 * `startWebServer()` returns as soon as it finds a manager, before it ever looks at
 * `external_base_uri`, so once that suite has run the option is silently ignored and Panther's
 * base URI still points at its application — which is why every request here goes through
 * {@see url()} and is absolute. The reverse cannot happen: phpunit.xml.dist declares `orm` before
 * `adminata-functional`, so that suite always claims the web server first.
 */
abstract class BasePantherTestCase extends PantherTestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        $options = [
            'external_base_uri' => DemoServer::baseUri(),
            'connection_timeout_in_ms' => 5000,
            'request_timeout_in_ms' => 60000,
        ];

        $seleniumHost = self::stringFromServer('PANTHER_SELENIUM_HOST');

        if (null !== $seleniumHost) {
            $this->client = static::createPantherClient(
                ['browser' => PantherTestCase::SELENIUM] + $options,
                [],
                ['host' => $seleniumHost, 'capabilities' => DesiredCapabilities::firefox()],
            );

            return;
        }

        $port = self::stringFromServer('PANTHER_FIREFOX_PORT');

        $this->client = static::createPantherClient(
            ['browser' => PantherTestCase::FIREFOX] + $options,
            [],
            null !== $port ? ['port' => (int) $port] : [],
        );
    }

    /**
     * The absolute address of a demo page, credentials included.
     *
     * Firefox accepts a top-level navigation to a URL carrying credentials, which is what spares
     * the demo a login form it does not otherwise need.
     */
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

    private static function stringFromServer(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? null;

        return \is_string($value) && '' !== $value ? $value : null;
    }
}
