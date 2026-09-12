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

namespace IDCT\Adminata\Tests\Form;

use IDCT\Adminata\Form\FormErrorIteratorToConstraintViolationList;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Jordi Sala <jordism91@gmail.com>
 */
final class FormErrorIteratorToConstraintViolationListTest extends TestCase
{
    /**
     * @param FormErrorIterator<FormError> $formErrors
     */
    #[DataProvider('provideTransformCases')]
    public function testTransform(int $expectedCount, FormErrorIterator $formErrors): void
    {
        $violationList = FormErrorIteratorToConstraintViolationList::transform($formErrors);

        static::assertInstanceOf(ConstraintViolationList::class, $violationList);
        static::assertCount($expectedCount, $violationList);
    }

    /**
     * @phpstan-return iterable<array{int, FormErrorIterator<FormError>}>
     */
    public static function provideTransformCases(): iterable
    {
        $form = static::createStub(FormInterface::class);
        $form->method('getName')->willReturn('name');

        // The generic argument is spelled out: `FormErrorIterator`'s own template bound mentions
        // `FormErrorIterator` unparameterised, and PHPStan resolves that inconsistently between
        // parallel workers when it has to infer T from the constructor argument.
        /** @var FormErrorIterator<FormError> $empty */
        $empty = new FormErrorIterator($form, []);

        yield [0, $empty];

        /** @var FormErrorIterator<FormError> $withoutViolation */
        $withoutViolation = new FormErrorIterator($form, [
            new FormError('error'),
        ]);

        yield [0, $withoutViolation];

        /** @var FormErrorIterator<FormError> $withViolation */
        $withViolation = new FormErrorIterator($form, [
            new FormError(
                'error',
                null,
                [],
                null,
                new ConstraintViolation('error', null, [], $form, 'path', 'invalid value')
            ),
        ]);

        yield [1, $withViolation];
    }
}
