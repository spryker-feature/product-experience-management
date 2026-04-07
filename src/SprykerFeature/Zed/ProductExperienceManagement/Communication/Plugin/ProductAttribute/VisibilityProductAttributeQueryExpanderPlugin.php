<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute;

use Generated\Shared\Transfer\ProductAttributeQueryCriteriaTransfer;
use Orm\Zed\ProductAttribute\Persistence\Map\SpyProductManagementAttributeTableMap;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeExtension\Dependency\Plugin\ProductAttributeQueryExpanderPluginInterface;

class VisibilityProductAttributeQueryExpanderPlugin extends AbstractPlugin implements ProductAttributeQueryExpanderPluginInterface
{
    protected const string COL_VISIBILITY = 'visibility';

    /**
     * {@inheritDoc}
     * - Adds the visibility column to `ProductAttributeQueryCriteriaTransfer`.
     *
     * @api
     */
    public function expandProductAttributeQueryCriteria(
        ProductAttributeQueryCriteriaTransfer $productAttributeQueryCriteriaTransfer,
    ): ProductAttributeQueryCriteriaTransfer {
        $withColumns = $productAttributeQueryCriteriaTransfer->getWithColumns();
        $withColumns[SpyProductManagementAttributeTableMap::COL_VISIBILITY] = static::COL_VISIBILITY;

        return $productAttributeQueryCriteriaTransfer->setWithColumns($withColumns);
    }
}
