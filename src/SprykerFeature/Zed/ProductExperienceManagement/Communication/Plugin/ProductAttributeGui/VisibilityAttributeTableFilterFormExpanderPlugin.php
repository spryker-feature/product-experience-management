<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableFilterFormExpanderPluginInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Communication\ProductExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class VisibilityAttributeTableFilterFormExpanderPlugin extends AbstractPlugin implements AttributeTableFilterFormExpanderPluginInterface
{
    protected const string FIELD_VISIBILITY_TYPES = 'visibilityTypes';

    protected const string LABEL_DISPLAY_AT = 'Display At';

    protected const string PLACEHOLDER_SELECT_DISPLAY_AT = 'Select Display At';

    protected const string OPTION_VISIBILITY_TYPE_CHOICES = 'visibility_type_choices';

    protected const string CSS_CLASS_SELECT2 = 'spryker-form-select2combobox';

    /**
     * {@inheritDoc}
     * - Adds the visibility type filter field to the table filter form.
     *
     * @api
     *
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(static::FIELD_VISIBILITY_TYPES, ChoiceType::class, [
            'label' => static::LABEL_DISPLAY_AT,
            'placeholder' => static::PLACEHOLDER_SELECT_DISPLAY_AT,
            'required' => false,
            'choices' => $options[static::OPTION_VISIBILITY_TYPE_CHOICES] ?? [],
            'multiple' => true,
            'attr' => [
                'class' => static::CSS_CLASS_SELECT2,
                'data-placeholder' => static::PLACEHOLDER_SELECT_DISPLAY_AT,
                'data-clearable' => true,
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     * - Returns visibility type choices including the internal type for filter options.
     *
     * @api
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->getFactory()
            ->createVisibilityAttributeTableExpander()
            ->getFilterFormOptions();
    }
}
