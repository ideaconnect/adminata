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

namespace Sonata\Form\Tests\Bridge\Symfony\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Sonata\Form\Bridge\Symfony\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    public function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    public function testInvalidFormTypeValueLeadsToErrorMessage(): void
    {
        $configs = [
            ['form_type' => '3D'],
        ];

        $this->assertConfigurationIsInvalid($configs);

        // The `$expectedMessage` argument of assertConfigurationIsInvalid() is unusable on
        // PHPUnit 13: matthiasnoback/symfony-config-test 6.2.0 hands the exception object to
        // PHPUnit's ExceptionMessageIsOrContains constraint, which since PHPUnit 13 matches
        // against the message string instead. Assert the message directly.
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The form_type option value must be one of');

        new Processor()->processConfiguration($this->getConfiguration(), $configs);
    }

    public function testProcessedConfigurationLooksAsExpected(): void
    {
        $this->assertProcessedConfigurationEquals([
            ['form_type' => 'horizontal'], // this should be overwritten
            ['form_type' => 'standard'],    // by this during the merge
        ], [
            'form_type' => 'standard',
        ]);
    }

    public function testDefault(): void
    {
        $this->assertProcessedConfigurationEquals([
            [],
        ], [
            'form_type' => 'standard',
        ]);
    }
}
