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

namespace IDCT\Adminata\Tests\App\Admin;

use IDCT\Adminata\Admin\AbstractAdmin;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;
use IDCT\Adminata\Form\FormMapper;
use IDCT\Adminata\Show\ShowMapper;
use IDCT\Adminata\Tests\App\Model\Translated;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @phpstan-extends AbstractAdmin<Translated>
 */
final class TranslatedAdmin extends AbstractAdmin
{
    protected function createNewInstance(): object
    {
        return new Translated();
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list->add('name_list', FieldDescriptionInterface::TYPE_STRING);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name_form', TextType::class, ['help' => 'Help me!'])
            ->ifTrue($this->getSubject()->isPublished)
                ->add('datePublished')
            ->ifEnd();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show->add('name_show', FieldDescriptionInterface::TYPE_STRING);
    }
}
