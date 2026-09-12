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

namespace IDCT\Adminata\Block\Service;

use IDCT\Adminata\Form\BlockFormMapperInterface;
use IDCT\Adminata\Meta\MetadataInterface;
use IDCT\Adminata\Model\BlockInterface;
use IDCT\Adminata\Validator\ErrorElement;

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
