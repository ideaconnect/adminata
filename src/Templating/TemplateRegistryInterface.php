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

namespace IDCT\Adminata\Templating;

use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;

/**
 * @author Timo Bakx <timobakx@gmail.com>
 */
interface TemplateRegistryInterface
{
    /**
     * @internal
     */
    public const SHOW_TEMPLATES = [
        FieldDescriptionInterface::TYPE_ARRAY => '@Adminata/CRUD/show_array.html.twig',
        FieldDescriptionInterface::TYPE_BOOLEAN => '@Adminata/CRUD/show_boolean.html.twig',
        FieldDescriptionInterface::TYPE_DATE => '@Adminata/CRUD/show_date.html.twig',
        FieldDescriptionInterface::TYPE_TIME => '@Adminata/CRUD/show_time.html.twig',
        FieldDescriptionInterface::TYPE_DATETIME => '@Adminata/CRUD/show_datetime.html.twig',
        FieldDescriptionInterface::TYPE_EMAIL => '@Adminata/CRUD/show_email.html.twig',
        FieldDescriptionInterface::TYPE_ENUM => '@Adminata/CRUD/show_enum.html.twig',
        FieldDescriptionInterface::TYPE_TRANS => '@Adminata/CRUD/show_trans.html.twig',
        FieldDescriptionInterface::TYPE_STRING => '@Adminata/CRUD/base_show_field.html.twig',
        FieldDescriptionInterface::TYPE_INTEGER => '@Adminata/CRUD/base_show_field.html.twig',
        FieldDescriptionInterface::TYPE_FLOAT => '@Adminata/CRUD/base_show_field.html.twig',
        FieldDescriptionInterface::TYPE_CURRENCY => '@Adminata/CRUD/show_currency.html.twig',
        FieldDescriptionInterface::TYPE_PERCENT => '@Adminata/CRUD/show_percent.html.twig',
        FieldDescriptionInterface::TYPE_CHOICE => '@Adminata/CRUD/show_choice.html.twig',
        FieldDescriptionInterface::TYPE_URL => '@Adminata/CRUD/show_url.html.twig',
        FieldDescriptionInterface::TYPE_HTML => '@Adminata/CRUD/show_html.html.twig',
        FieldDescriptionInterface::TYPE_MANY_TO_MANY => '@Adminata/CRUD/Association/show_many_to_many.html.twig',
        FieldDescriptionInterface::TYPE_MANY_TO_ONE => '@Adminata/CRUD/Association/show_many_to_one.html.twig',
        FieldDescriptionInterface::TYPE_ONE_TO_MANY => '@Adminata/CRUD/Association/show_one_to_many.html.twig',
        FieldDescriptionInterface::TYPE_ONE_TO_ONE => '@Adminata/CRUD/Association/show_one_to_one.html.twig',
    ];

    /**
     * @internal
     */
    public const LIST_TEMPLATES = [
        FieldDescriptionInterface::TYPE_ARRAY => '@Adminata/CRUD/list_array.html.twig',
        FieldDescriptionInterface::TYPE_BOOLEAN => '@Adminata/CRUD/list_boolean.html.twig',
        FieldDescriptionInterface::TYPE_DATE => '@Adminata/CRUD/list_date.html.twig',
        FieldDescriptionInterface::TYPE_TIME => '@Adminata/CRUD/list_time.html.twig',
        FieldDescriptionInterface::TYPE_DATETIME => '@Adminata/CRUD/list_datetime.html.twig',
        FieldDescriptionInterface::TYPE_TEXTAREA => '@Adminata/CRUD/list_string.html.twig',
        FieldDescriptionInterface::TYPE_EMAIL => '@Adminata/CRUD/list_email.html.twig',
        FieldDescriptionInterface::TYPE_ENUM => '@Adminata/CRUD/list_enum.html.twig',
        FieldDescriptionInterface::TYPE_TRANS => '@Adminata/CRUD/list_trans.html.twig',
        FieldDescriptionInterface::TYPE_STRING => '@Adminata/CRUD/list_string.html.twig',
        FieldDescriptionInterface::TYPE_INTEGER => '@Adminata/CRUD/list_string.html.twig',
        FieldDescriptionInterface::TYPE_FLOAT => '@Adminata/CRUD/list_string.html.twig',
        FieldDescriptionInterface::TYPE_IDENTIFIER => '@Adminata/CRUD/list_string.html.twig',
        FieldDescriptionInterface::TYPE_CURRENCY => '@Adminata/CRUD/list_currency.html.twig',
        FieldDescriptionInterface::TYPE_PERCENT => '@Adminata/CRUD/list_percent.html.twig',
        FieldDescriptionInterface::TYPE_CHOICE => '@Adminata/CRUD/list_choice.html.twig',
        FieldDescriptionInterface::TYPE_URL => '@Adminata/CRUD/list_url.html.twig',
        FieldDescriptionInterface::TYPE_HTML => '@Adminata/CRUD/list_html.html.twig',
        FieldDescriptionInterface::TYPE_MANY_TO_MANY => '@Adminata/CRUD/Association/list_many_to_many.html.twig',
        FieldDescriptionInterface::TYPE_MANY_TO_ONE => '@Adminata/CRUD/Association/list_many_to_one.html.twig',
        FieldDescriptionInterface::TYPE_ONE_TO_MANY => '@Adminata/CRUD/Association/list_one_to_many.html.twig',
        FieldDescriptionInterface::TYPE_ONE_TO_ONE => '@Adminata/CRUD/Association/list_one_to_one.html.twig',
    ];

    /**
     * @return array<string, string> 'name' => 'file_path.html.twig'
     */
    public function getTemplates(): array;

    public function getTemplate(string $name): string;

    public function hasTemplate(string $name): bool;
}
