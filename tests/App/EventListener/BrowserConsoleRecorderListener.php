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

namespace Adminata\Tests\App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Records everything the page writes to the browser console, so a Panther test can assert it is
 * empty (PLAN/08 §3).
 *
 * geckodriver implements no log endpoint — `manage()->getLog('browser')` is a Chrome extension to
 * WebDriver — so the page has to keep the record itself. Injecting it from a response listener
 * rather than from a template means the M2 to M4 rewrites cannot lose it, and putting it first in
 * the `<head>` means it is installed before any of the page's own scripts can write.
 *
 * Registered only in the test environment (tests/App/config/services.php).
 */
#[AsEventListener(event: KernelEvents::RESPONSE, priority: -1024)]
final class BrowserConsoleRecorderListener
{
    public const string PROPERTY = '__adminataConsole';

    private const string SCRIPT = <<<'JS'
        <script>
        (function () {
            window.PROPERTY = [];

            for (const level of ['error', 'warn']) {
                const original = console[level].bind(console);

                console[level] = function (...args) {
                    window.PROPERTY.push(level + ': ' + args.map(String).join(' '));
                    original(...args);
                };
            }

            window.addEventListener('error', function (event) {
                window.PROPERTY.push('uncaught: ' + (event.message ?? String(event.error)));
            });

            window.addEventListener('unhandledrejection', function (event) {
                window.PROPERTY.push('unhandled rejection: ' + String(event.reason));
            });
        })();
        </script>
        JS;

    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $content = $response->getContent();
        // Absent means HTML: `Response::prepare()` fills the header in after this event, so a
        // response that never set one explicitly still has none here.
        $contentType = $response->headers->get('Content-Type');

        if (!\is_string($content) || (null !== $contentType && !str_contains($contentType, 'text/html'))) {
            return;
        }

        $position = stripos($content, '<head>');

        if (false === $position) {
            return;
        }

        $offset = $position + \strlen('<head>');
        $script = str_replace('PROPERTY', self::PROPERTY, self::SCRIPT);

        $response->setContent(substr($content, 0, $offset).$script.substr($content, $offset));
    }
}
