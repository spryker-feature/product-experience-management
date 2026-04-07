<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander;

use Generated\Shared\Transfer\ProductManagementAttributeTransfer;
use Symfony\Component\Form\FormInterface;

interface VisibilityAttributeFormExpanderInterface
{
    /**
     * Expands form data with visibility types for create and edit modes.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function expandFormData(array $data, ?ProductManagementAttributeTransfer $productManagementAttributeTransfer): array;

    /**
     * Expands form options with visibility type choices.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function expandFormOptions(array $options): array;

    /**
     * Maps visibility types form field to comma-separated string on the transfer.
     */
    public function mapVisibilityToTransfer(
        ProductManagementAttributeTransfer $productManagementAttributeTransfer,
        FormInterface $form,
    ): ProductManagementAttributeTransfer;
}
