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

namespace Sonata\AdminBundle\Block\Service;

use Sonata\AdminBundle\Form\BlockFormMapperInterface;
use Sonata\AdminBundle\Meta\MetadataInterface;
use Sonata\AdminBundle\Model\BlockInterface;
use Sonata\AdminBundle\Validator\ErrorElement;

/**
 * @author Christian Gripp <mail@core23.de>
 */
interface EditableBlockService
{
    public function configureEditForm(BlockFormMapperInterface $form, BlockInterface $block): void;

    public function configureCreateForm(BlockFormMapperInterface $form, BlockInterface $block): void;

    public function validate(ErrorElement $errorElement, BlockInterface $block): void;

    public function getMetadata(): MetadataInterface;
}
