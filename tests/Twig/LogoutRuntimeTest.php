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

use IDCT\Adminata\Twig\LogoutRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

final class LogoutRuntimeTest extends TestCase
{
    public function testTheCurrentFirewallsLogoutPathIsReturned(): void
    {
        $generator = $this->generator();
        $generator->registerListener('admin', '/admin/logout', null, null);
        $generator->setCurrentFirewall('admin');

        static::assertSame('/admin/logout', new LogoutRuntime($generator)->getLogoutPath());
    }

    public function testTheCsrfTokenIsCarriedWhenTheFirewallAsksForOne(): void
    {
        $tokens = static::createStub(CsrfTokenManagerInterface::class);
        $tokens->method('getToken')->willReturn(new CsrfToken('logout', 'abc'));

        $generator = $this->generator();
        $generator->registerListener('admin', '/admin/logout', 'logout', '_csrf_token', $tokens);
        $generator->setCurrentFirewall('admin');

        static::assertSame('/admin/logout?_csrf_token=abc', new LogoutRuntime($generator)->getLogoutPath());
    }

    public function testAFirewallWithoutALogoutGivesNoPath(): void
    {
        $generator = $this->generator();
        $generator->registerListener('admin', '/admin/logout', null, null);
        $generator->setCurrentFirewall('api');

        static::assertNull(new LogoutRuntime($generator)->getLogoutPath());
    }

    public function testARequestBehindNoFirewallGivesNoPath(): void
    {
        $generator = $this->generator();
        $generator->registerListener('admin', '/admin/logout', null, null);

        static::assertNull(new LogoutRuntime($generator)->getLogoutPath());
    }

    public function testAnApplicationWithoutSecurityGivesNoPath(): void
    {
        static::assertNull(new LogoutRuntime(null)->getLogoutPath());
    }

    private function generator(): LogoutUrlGenerator
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://example.com/admin/dashboard'));

        return new LogoutUrlGenerator($requestStack);
    }
}
