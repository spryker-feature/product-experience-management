<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander;

use Generated\Shared\Transfer\ProductAttributeTableCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttributeTableQueryConditionTransfer;
use Orm\Zed\ProductAttribute\Persistence\Map\SpyProductManagementAttributeTableMap;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig as SharedProductExperienceManagementConfig;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig;

class VisibilityAttributeTableExpander implements VisibilityAttributeTableExpanderInterface
{
    protected const string COL_VISIBILITY = 'visibility';

    protected const string BADGE_TEMPLATE = '<span class="badge text-bg-light">%s</span>';

    protected const string CONDITION_NULL_OR_EMPTY_TEMPLATE = '(%s IS NULL OR %s = ?)';

    protected const string CONDITION_LIKE_TEMPLATE = ' LIKE ?';

    protected const string LIKE_PATTERN = '%%%s%%';

    protected const string COMBINE_OPERATOR = 'or';

    protected const string OPTION_VISIBILITY_TYPE_CHOICES = 'visibility_type_choices';

    public function __construct(
        protected readonly ProductExperienceManagementConfig $productExperienceManagementConfig,
    ) {
    }

    public function expandTableCriteria(
        ProductAttributeTableCriteriaTransfer $productAttributeTableCriteriaTransfer,
    ): ProductAttributeTableCriteriaTransfer {
        $withColumns = $productAttributeTableCriteriaTransfer->getWithColumns();
        $withColumns[SpyProductManagementAttributeTableMap::COL_VISIBILITY] = static::COL_VISIBILITY;
        $productAttributeTableCriteriaTransfer->setWithColumns($withColumns);

        $this->expandWithVisibilityConditions($productAttributeTableCriteriaTransfer);

        return $productAttributeTableCriteriaTransfer;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    public function expandTableData(array $item): array
    {
        $visibility = $item[static::COL_VISIBILITY] ?? '';

        if ($visibility === '') {
            return [static::COL_VISIBILITY => ''];
        }

        $labels = [];

        foreach (explode(',', $visibility) as $type) {
            $labels[] = sprintf(static::BADGE_TEMPLATE, trim($type));
        }

        return [static::COL_VISIBILITY => implode(' ', $labels)];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function getFilterFormOptions(): array
    {
        $choices = [];

        foreach ($this->productExperienceManagementConfig->getAvailableVisibilityTypes() as $type) {
            $choices[$type] = $type;
        }

        $choices[SharedProductExperienceManagementConfig::VISIBILITY_TYPE_INTERNAL] = SharedProductExperienceManagementConfig::VISIBILITY_TYPE_INTERNAL;

        return [static::OPTION_VISIBILITY_TYPE_CHOICES => $choices];
    }

    protected function expandWithVisibilityConditions(
        ProductAttributeTableCriteriaTransfer $productAttributeTableCriteriaTransfer,
    ): void {
        $visibilityTypes = $productAttributeTableCriteriaTransfer->getVisibilityTypes();

        if (!$visibilityTypes) {
            return;
        }

        $productAttributeTableCriteriaTransfer->setConditionCombineOperator(static::COMBINE_OPERATOR);

        foreach ($visibilityTypes as $visibilityType) {
            if ($visibilityType === SharedProductExperienceManagementConfig::VISIBILITY_TYPE_INTERNAL) {
                $productAttributeTableCriteriaTransfer->addQueryCondition(
                    (new ProductAttributeTableQueryConditionTransfer())
                        ->setExpression(sprintf(
                            static::CONDITION_NULL_OR_EMPTY_TEMPLATE,
                            SpyProductManagementAttributeTableMap::COL_VISIBILITY,
                            SpyProductManagementAttributeTableMap::COL_VISIBILITY,
                        ))
                        ->setValue(''),
                );

                continue;
            }

            $productAttributeTableCriteriaTransfer->addQueryCondition(
                (new ProductAttributeTableQueryConditionTransfer())
                    ->setExpression(SpyProductManagementAttributeTableMap::COL_VISIBILITY . static::CONDITION_LIKE_TEMPLATE)
                    ->setValue(sprintf(static::LIKE_PATTERN, $visibilityType)),
            );
        }
    }
}
