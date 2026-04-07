<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander;

use Generated\Shared\Transfer\ProductManagementAttributeTransfer;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig;
use Symfony\Component\Form\FormInterface;

class VisibilityAttributeFormExpander implements VisibilityAttributeFormExpanderInterface
{
    protected const string FIELD_VISIBILITY_TYPES = 'visibility_types';

    protected const string OPTION_VISIBILITY_TYPE_CHOICES = 'visibility_type_choices';

    public function __construct(
        protected readonly ProductExperienceManagementConfig $productExperienceManagementConfig,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function expandFormData(array $data, ?ProductManagementAttributeTransfer $productManagementAttributeTransfer): array
    {
        if ($productManagementAttributeTransfer === null) {
            $data[static::FIELD_VISIBILITY_TYPES] = $this->productExperienceManagementConfig->getDefaultVisibilityTypes();

            return $data;
        }

        $visibility = $productManagementAttributeTransfer->getVisibility() ?? '';
        $data[static::FIELD_VISIBILITY_TYPES] = $visibility !== '' ? array_map('trim', explode(',', $visibility)) : [];

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function expandFormOptions(array $options): array
    {
        $choices = [];

        foreach ($this->productExperienceManagementConfig->getAvailableVisibilityTypes() as $type) {
            $choices[$type] = $type;
        }

        $options[static::OPTION_VISIBILITY_TYPE_CHOICES] = $choices;

        return $options;
    }

    public function mapVisibilityToTransfer(
        ProductManagementAttributeTransfer $productManagementAttributeTransfer,
        FormInterface $form,
    ): ProductManagementAttributeTransfer {
        $visibilityTypes = (array)$form->get(static::FIELD_VISIBILITY_TYPES)->getData();
        $productManagementAttributeTransfer->setVisibility(implode(',', $visibilityTypes));

        return $productManagementAttributeTransfer;
    }
}
