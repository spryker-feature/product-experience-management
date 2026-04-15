<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Locale\Persistence\Map\SpyLocaleTableMap;
use Orm\Zed\ProductImage\Persistence\Map\SpyProductImageSetTableMap;
use Orm\Zed\ProductImage\Persistence\Map\SpyProductImageSetToProductImageTableMap;
use Orm\Zed\ProductImage\Persistence\Map\SpyProductImageTableMap;
use Orm\Zed\ProductImage\Persistence\SpyProductImageSetToProductImageQuery;

class ProductCsvImageExportStep extends AbstractProductCsvExportStep
{
    protected const string IMAGE_SMALL_COLUMN_FORMAT = 'Image Small (%s-%d)';

    protected const string IMAGE_LARGE_COLUMN_FORMAT = 'Image Large (%s-%d)';

    protected const string DEFAULT_LOCALE = 'DEFAULT';

    protected const string ALIAS_ID_PRODUCT_ABSTRACT = 'IdProductAbstract';

    protected const string ALIAS_ID_PRODUCT = 'IdProduct';

    protected const string ALIAS_SORT_ORDER = 'SortOrder';

    protected const string ALIAS_SMALL_URL = 'SmallUrl';

    protected const string ALIAS_LARGE_URL = 'LargeUrl';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $rows = $this->populateAbstractImages($rows, $columns);
        $rows = $this->populateConcreteImages($rows, $columns);

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    protected function populateAbstractImages(array $rows, array $columns): array
    {
        $imagesByAbstractSku = $this->buildAbstractImageMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($imagesByAbstractSku[$abstractSku])) {
                continue;
            }

            $rows[$index] = $this->applyImageData($row, $columns, $imagesByAbstractSku[$abstractSku]);
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    protected function populateConcreteImages(array $rows, array $columns): array
    {
        $imagesByConcreteSku = $this->buildConcreteImageMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isConcreteRow($row)) {
                continue;
            }

            $concreteSku = $row[static::COLUMN_CONCRETE_SKU];

            if (!isset($imagesByConcreteSku[$concreteSku])) {
                continue;
            }

            $rows[$index] = $this->applyImageData($row, $columns, $imagesByConcreteSku[$concreteSku]);
        }

        return $rows;
    }

    /**
     * @param array<string, string> $row
     * @param array<string> $columns
     * @param array<int, array<string, mixed>> $imageEntries
     *
     * @return array<string, string>
     */
    protected function applyImageData(array $row, array $columns, array $imageEntries): array
    {
        foreach ($imageEntries as $imageData) {
            $smallColumn = sprintf(static::IMAGE_SMALL_COLUMN_FORMAT, $imageData['locale'], $imageData['sortOrder']);
            $largeColumn = sprintf(static::IMAGE_LARGE_COLUMN_FORMAT, $imageData['locale'], $imageData['sortOrder']);

            $row = $this->setColumn($row, $columns, $smallColumn, $imageData['smallUrl']);
            $row = $this->setColumn($row, $columns, $largeColumn, $imageData['largeUrl']);
        }

        return $row;
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function buildAbstractImageMap(array $rows): array
    {
        $abstractIdToSkuMap = $this->extractAbstractIdToSkuMap($rows);

        if ($abstractIdToSkuMap === []) {
            return [];
        }

        $imageRelations = SpyProductImageSetToProductImageQuery::create()
            ->joinWithSpyProductImageSet()
            ->joinWithSpyProductImage()
            ->useSpyProductImageSetQuery()
                ->filterByFkProductAbstract(array_keys($abstractIdToSkuMap), SpyProductImageSetToProductImageQuery::IN)
                ->leftJoinWithSpyLocale()
            ->endUse()
            ->withColumn(SpyProductImageSetTableMap::COL_FK_PRODUCT_ABSTRACT, static::ALIAS_ID_PRODUCT_ABSTRACT)
            ->withColumn(SpyLocaleTableMap::COL_LOCALE_NAME, static::ALIAS_LOCALE_NAME)
            ->withColumn(SpyProductImageSetToProductImageTableMap::COL_SORT_ORDER, static::ALIAS_SORT_ORDER)
            ->withColumn(SpyProductImageTableMap::COL_EXTERNAL_URL_SMALL, static::ALIAS_SMALL_URL)
            ->withColumn(SpyProductImageTableMap::COL_EXTERNAL_URL_LARGE, static::ALIAS_LARGE_URL)
            ->select([static::ALIAS_ID_PRODUCT_ABSTRACT, static::ALIAS_LOCALE_NAME, static::ALIAS_SORT_ORDER, static::ALIAS_SMALL_URL, static::ALIAS_LARGE_URL])
            ->find()
            ->getData();

        return $this->groupImageRelations($imageRelations, static::ALIAS_ID_PRODUCT_ABSTRACT, $abstractIdToSkuMap);
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function buildConcreteImageMap(array $rows): array
    {
        $concreteIdToSkuMap = $this->extractConcreteIdToSkuMap($rows);

        if ($concreteIdToSkuMap === []) {
            return [];
        }

        $imageRelations = SpyProductImageSetToProductImageQuery::create()
            ->joinWithSpyProductImageSet()
            ->joinWithSpyProductImage()
            ->useSpyProductImageSetQuery()
                ->filterByFkProduct(array_keys($concreteIdToSkuMap), SpyProductImageSetToProductImageQuery::IN)
                ->leftJoinWithSpyLocale()
            ->endUse()
            ->withColumn(SpyProductImageSetTableMap::COL_FK_PRODUCT, static::ALIAS_ID_PRODUCT)
            ->withColumn(SpyLocaleTableMap::COL_LOCALE_NAME, static::ALIAS_LOCALE_NAME)
            ->withColumn(SpyProductImageSetToProductImageTableMap::COL_SORT_ORDER, static::ALIAS_SORT_ORDER)
            ->withColumn(SpyProductImageTableMap::COL_EXTERNAL_URL_SMALL, static::ALIAS_SMALL_URL)
            ->withColumn(SpyProductImageTableMap::COL_EXTERNAL_URL_LARGE, static::ALIAS_LARGE_URL)
            ->select([static::ALIAS_ID_PRODUCT, static::ALIAS_LOCALE_NAME, static::ALIAS_SORT_ORDER, static::ALIAS_SMALL_URL, static::ALIAS_LARGE_URL])
            ->find()
            ->getData();

        return $this->groupImageRelations($imageRelations, static::ALIAS_ID_PRODUCT, $concreteIdToSkuMap);
    }

    /**
     * @param array<int, array<string, mixed>> $imageRelations
     * @param array<int, string> $idToSkuMap
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function groupImageRelations(array $imageRelations, string $idAlias, array $idToSkuMap): array
    {
        $imagesBySku = [];

        foreach ($imageRelations as $imageRelation) {
            $productId = $imageRelation[$idAlias];

            if (!isset($idToSkuMap[$productId])) {
                continue;
            }

            $sku = $idToSkuMap[$productId];
            $locale = $imageRelation[static::ALIAS_LOCALE_NAME] !== null
                ? $this->formatLocale($imageRelation[static::ALIAS_LOCALE_NAME])
                : static::DEFAULT_LOCALE;

            $imagesBySku[$sku][] = [
                'locale' => $locale,
                'sortOrder' => (int)$imageRelation[static::ALIAS_SORT_ORDER],
                'smallUrl' => $imageRelation[static::ALIAS_SMALL_URL] ?? '',
                'largeUrl' => $imageRelation[static::ALIAS_LARGE_URL] ?? '',
            ];
        }

        return $imagesBySku;
    }
}
