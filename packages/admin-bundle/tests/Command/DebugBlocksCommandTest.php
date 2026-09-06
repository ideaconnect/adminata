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

namespace Sonata\AdminBundle\Tests\Command;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Block\BlockServiceManagerInterface;
use Sonata\AdminBundle\Block\Service\AbstractBlockService;
use Sonata\AdminBundle\Block\Service\EditableBlockService;
use Sonata\AdminBundle\Command\DebugBlocksCommand;
use Sonata\AdminBundle\Form\BlockFormMapperInterface;
use Sonata\AdminBundle\Meta\Metadata;
use Sonata\AdminBundle\Meta\MetadataInterface;
use Sonata\AdminBundle\Model\BlockInterface;
use Sonata\AdminBundle\Validator\ErrorElement;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class DebugBlocksCommandTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $this->application = new Application();

        $blockManager = $this->createMock(BlockServiceManagerInterface::class);
        $blockManager
            ->expects(static::any())
            ->method('getServices')
            ->willReturn([]);

        $this->application->addCommand(new DebugBlocksCommand($blockManager));
    }

    protected function tearDown(): void
    {
        unset($this->application);
    }

    public function testExecuteWithAlias(): void
    {
        $command = $this->application->find('debug:sonata:block');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => 'debug:sonata:block']);

        static::assertSame("done!\n", $commandTester->getDisplay());
    }

    #[Group('legacy')]
    public function testDebugBlocks(): void
    {
        $this->application = new Application();
        $twig = $this->createMock(Environment::class);

        $blockManager = $this->createMock(BlockServiceManagerInterface::class);
        $blockManager
            ->expects(static::any())
            ->method('getServices')
            ->willReturn([
                'test.without_options' => new class($twig) extends AbstractBlockService {
                },
                'test.with_simple_option' => new class($twig) extends AbstractBlockService {
                    public function configureSettings(OptionsResolver $resolver): void
                    {
                        $resolver->setDefault('limit', 150);
                        $resolver->setAllowedTypes('limit', 'int');
                    }
                },
                'test.with_required_option' => new class($twig) extends AbstractBlockService {
                    public function configureSettings(OptionsResolver $resolver): void
                    {
                        $resolver->setRequired('limit');
                        $resolver->setAllowedTypes('limit', 'int');
                    }
                },
                'test.with_metadata' => new class($twig) extends AbstractBlockService implements EditableBlockService {
                    public function configureEditForm(BlockFormMapperInterface $form, BlockInterface $block): void
                    {
                    }

                    public function configureCreateForm(BlockFormMapperInterface $form, BlockInterface $block): void
                    {
                    }

                    public function validate(ErrorElement $errorElement, BlockInterface $block): void
                    {
                    }

                    public function getMetadata(): MetadataInterface
                    {
                        return new Metadata('My block title');
                    }
                },
            ]);

        $this->application->addCommand(new DebugBlocksCommand($blockManager));

        $command = $this->application->find('debug:sonata:block');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => 'debug:sonata:block']);

        $expected = <<<EOF

            >> test.without_options

            >> test.with_simple_option
                limit                         150

            >> test.with_required_option
                limit

            >> test.with_metadata (My block title)
            done!

            EOF;

        static::assertSame($expected, $commandTester->getDisplay());
    }
}
