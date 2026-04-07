<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Spryker\Zed\Gui\Communication\Form\Type\Select2ComboBoxType;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeFormExpanderPluginInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;

class VisibilityAttributeFormExpanderPlugin extends AbstractPlugin implements AttributeFormExpanderPluginInterface
{
    protected const string FIELD_VISIBILITY_TYPES = 'visibility_types';

    protected const string OPTION_VISIBILITY_TYPE_CHOICES = 'visibility_type_choices';

    protected const string LABEL_DISPLAY_AT = 'Display At';

    /**
     * {@inheritDoc}
     * - Adds the visibility types select field to the attribute form.
     *
     * @api
     *
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $options[static::OPTION_VISIBILITY_TYPE_CHOICES] ?? [];

        $builder->add(static::FIELD_VISIBILITY_TYPES, Select2ComboBoxType::class, [
            'label' => static::LABEL_DISPLAY_AT,
            'choices' => $choices,
            'multiple' => true,
            'constraints' => [
                new Choice([
                    'choices' => array_values($choices),
                    'multiple' => true,
                ]),
            ],
        ]);
    }
}
