<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute;

use Generated\Shared\Transfer\ProductAttributeQueryCriteriaTransfer;
use Orm\Zed\ProductAttribute\Persistence\Map\SpyProductManagementAttributeTableMap;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeExtension\Dependency\Plugin\SuggestKeysQueryExpanderPluginInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class VisibilitySuggestKeysQueryExpanderPlugin extends AbstractPlugin implements SuggestKeysQueryExpanderPluginInterface
{
    protected const string COL_VISIBILITY = 'visibility';

    /**
     * {@inheritDoc}
     * - Adds the `visibility` column to the suggest keys query result set.
     *
     * @api
     */
    public function expandSuggestKeysQueryCriteria(
        ProductAttributeQueryCriteriaTransfer $productAttributeQueryCriteriaTransfer,
    ): ProductAttributeQueryCriteriaTransfer {
        $withColumns = $productAttributeQueryCriteriaTransfer->getWithColumns();
        $withColumns[SpyProductManagementAttributeTableMap::COL_VISIBILITY] = static::COL_VISIBILITY;

        return $productAttributeQueryCriteriaTransfer->setWithColumns($withColumns);
    }
}
