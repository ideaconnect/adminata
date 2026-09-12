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

namespace IDCT\Adminata\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @author Jordi Sala <jordism91@gmail.com>
 */
final class FormErrorIteratorToConstraintViolationList
{
    /**
     * The parameter deliberately carries no `@param FormErrorIterator<FormError>`.
     * `FormErrorIterator` names itself in its own template bound, and PHPStan rejects the type it
     * infers for `getErrors()` against the same type written as that annotation — see the note
     * beside `skipCheckGenericClasses` in phpstan.neon.dist. The `instanceof` below says what the
     * annotation used to, and says it to the runtime as well.
     *
     * `getErrors($deep, $flatten = true)` yields `FormError`s. Ask for `$flatten = false` and the
     * iterator yields child iterators instead; upstream handed those to `buildViolation()`, which
     * is typed for a `FormError` and would have raised a TypeError. They are skipped.
     */
    public static function transform(FormErrorIterator $errors, bool $removeSensitiveData = false): ConstraintViolationListInterface
    {
        $form = $errors->getForm();
        $list = new ConstraintViolationList();

        foreach ($errors as $error) {
            if (!$error instanceof FormError) {
                continue;
            }

            $violation = static::buildViolation($error, $form, $removeSensitiveData);

            if (null === $violation) {
                continue;
            }

            $list->add($violation);
        }

        return $list;
    }

    private static function buildViolation(FormError $error, FormInterface $form, bool $removeSensitiveData): ?ConstraintViolationInterface
    {
        $cause = $error->getCause();

        if (!$cause instanceof ConstraintViolationInterface) {
            return null;
        }

        return new ConstraintViolation(
            $cause->getMessage(),
            $removeSensitiveData ? null : $cause->getMessageTemplate(),
            $removeSensitiveData ? [] : $cause->getParameters(),
            $cause->getRoot(),
            self::buildName($error->getOrigin() ?? $form),
            $cause->getInvalidValue(),
            $cause->getPlural(),
            $cause->getCode(),
        );
    }

    private static function buildName(FormInterface $form): string
    {
        $parent = $form->getParent();

        if (null === $parent) {
            return $form->getName();
        }

        return self::buildName($parent).'['.$form->getName().']';
    }
}
