<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander;

use Generated\Shared\Transfer\ProductAttributeTableCriteriaTransfer;

interface VisibilityAttributeTableExpanderInterface
{
    /**
     * Expands `ProductAttributeTableCriteriaTransfer` with visibility `withColumns` and query conditions.
     */
    public function expandTableCriteria(
        ProductAttributeTableCriteriaTransfer $productAttributeTableCriteriaTransfer,
    ): ProductAttributeTableCriteriaTransfer;

    /**
     * Expands table row data with rendered visibility badges.
     *
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    public function expandTableData(array $item): array;

    /**
     * Returns filter form options with visibility type choices including the internal type.
     *
     * @return array<string, mixed>
     */
    public function getFilterFormOptions(): array;
}
