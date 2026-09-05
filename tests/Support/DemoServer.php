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

namespace Adminata\Tests\Support;

use Symfony\Component\Process\Process;

/**
 * The demo application, served by PHP's built-in server for the browser suites.
 *
 * Panther starts a web server of its own, but only one per process, and the
 * doctrine-orm-admin-bundle suite has already claimed it for its own application
 * (`PANTHER_WEB_SERVER_DIR` in phpunit.xml.dist). adminata's Panther tests therefore run against
 * this server and hand Panther its address as `external_base_uri`, which makes it skip starting
 * one.
 *
 * The server binds every interface: a Selenium in a container has to reach it, and it can only do
 * that through the host gateway.
 */
final class DemoServer
{
    private const int READY_ATTEMPTS = 60;

    private const int READY_INTERVAL_MICROSECONDS = 100_000;

    private static ?Process $process = null;

    /**
     * The address the *browser* dials, with the credentials of the demo's in-memory user.
     *
     * Firefox accepts a top-level navigation to a URL carrying credentials, which is what spares
     * the demo a login form it does not otherwise need.
     */
    public static function baseUri(): string
    {
        self::start();

        return \sprintf('http://admin:admin@%s:%d', self::browserHost(), self::port());
    }

    public static function start(): void
    {
        if (null !== self::$process) {
            return;
        }

        // Panther's own web server defaults to 9080 and the doctrine-orm-admin-bundle suite has
        // one running by the time these tests start; a port that answers is not proof that *this*
        // application is the one answering, and the failure it produces otherwise — an empty list
        // and a missing console recorder — says nothing about the cause.
        if (self::accepts()) {
            throw new \RuntimeException(\sprintf(
                'Port %d is already in use, so the demo cannot be served there. Set '
                .'ADMINATA_DEMO_PORT to a free port.',
                self::port()
            ));
        }

        $process = new Process(
            [\PHP_BINARY, '-S', \sprintf('0.0.0.0:%d', self::port()), '-t', \dirname(__DIR__).'/App/public'],
            \dirname(__DIR__, 2),
            ['APP_ENV' => 'test', 'APP_DEBUG' => '0'],
        );
        $process->setTimeout(null);
        $process->start();

        self::$process = $process;
        register_shutdown_function(self::stop(...));

        self::waitUntilReady();
    }

    public static function stop(): void
    {
        self::$process?->stop();
        self::$process = null;
    }

    private static function waitUntilReady(): void
    {
        for ($attempt = 0; $attempt < self::READY_ATTEMPTS; ++$attempt) {
            if (self::accepts()) {
                return;
            }

            usleep(self::READY_INTERVAL_MICROSECONDS);
        }

        self::stop();

        throw new \RuntimeException(\sprintf('The demo application did not answer on port %d.', self::port()));
    }

    private static function accepts(): bool
    {
        $connection = @fsockopen('127.0.0.1', self::port(), $errorCode, $errorMessage, 1);

        if (false === $connection) {
            return false;
        }

        fclose($connection);

        return true;
    }

    private static function port(): int
    {
        $port = $_SERVER['ADMINATA_DEMO_PORT'] ?? null;

        return \is_string($port) && '' !== $port ? (int) $port : 9088;
    }

    /**
     * `PANTHER_SELENIUM_HOST` all but always means Selenium in a container, and a container
     * reaches this server through the host gateway rather than through its own loopback.
     */
    private static function browserHost(): string
    {
        $host = $_SERVER['ADMINATA_DEMO_BROWSER_HOST'] ?? null;

        if (\is_string($host) && '' !== $host) {
            return $host;
        }

        $selenium = $_SERVER['PANTHER_SELENIUM_HOST'] ?? $_ENV['PANTHER_SELENIUM_HOST'] ?? null;

        return \is_string($selenium) && '' !== $selenium ? 'host.docker.internal' : '127.0.0.1';
    }
}
