<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Locale\Persistence\Map\SpyLocaleTableMap;
use Orm\Zed\Product\Persistence\Map\SpyProductAbstractLocalizedAttributesTableMap;
use Orm\Zed\Product\Persistence\Map\SpyProductLocalizedAttributesTableMap;
use Orm\Zed\Product\Persistence\SpyProductAbstractLocalizedAttributesQuery;
use Orm\Zed\Product\Persistence\SpyProductLocalizedAttributesQuery;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;

class ProductCsvLocalizedAttributesExportStep extends AbstractProductCsvExportStep
{
    public function __construct(
        protected UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    protected const string ATTRIBUTE_FORMAT = '%s=%s';

    protected const string LOCALIZED_COLUMN_FORMAT = '%s (%s)';

    protected const string COLUMN_PREFIX_NAME = 'Name';

    protected const string COLUMN_PREFIX_DESCRIPTION = 'Description';

    protected const string COLUMN_PREFIX_ATTRIBUTES = 'Attributes';

    protected const string ALIAS_FK_PRODUCT_ABSTRACT = 'FkProductAbstract';

    protected const string ALIAS_NAME = 'Name';

    protected const string ALIAS_DESCRIPTION = 'Description';

    protected const string ALIAS_ATTRIBUTES = 'Attributes';

    protected const string ALIAS_FK_PRODUCT = 'FkProduct';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $rows = $this->populateAbstractLocalizedAttributes($rows, $columns);
        $rows = $this->populateConcreteLocalizedAttributes($rows, $columns);

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    protected function populateAbstractLocalizedAttributes(array $rows, array $columns): array
    {
        $abstractLocalizedMap = $this->buildAbstractLocalizedMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($abstractLocalizedMap[$abstractSku])) {
                continue;
            }

            foreach ($abstractLocalizedMap[$abstractSku] as $locale => $data) {
                $row = $this->setColumn(
                    $row,
                    $columns,
                    sprintf(static::LOCALIZED_COLUMN_FORMAT, static::COLUMN_PREFIX_NAME, $locale),
                    $data['name'],
                );

                $row = $this->setColumn(
                    $row,
                    $columns,
                    sprintf(static::LOCALIZED_COLUMN_FORMAT, static::COLUMN_PREFIX_DESCRIPTION, $locale),
                    $data['description'],
                );

                if ($data['attributes'] !== '') {
                    $row = $this->setColumn(
                        $row,
                        $columns,
                        sprintf(static::LOCALIZED_COLUMN_FORMAT, static::COLUMN_PREFIX_ATTRIBUTES, $locale),
                        $data['attributes'],
                    );
                }
            }

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    protected function populateConcreteLocalizedAttributes(array $rows, array $columns): array
    {
        $concreteLocalizedMap = $this->buildConcreteLocalizedMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isConcreteRow($row)) {
                continue;
            }

            $concreteSku = $row[static::COLUMN_CONCRETE_SKU];

            if (!isset($concreteLocalizedMap[$concreteSku])) {
                continue;
            }

            foreach ($concreteLocalizedMap[$concreteSku] as $locale => $data) {
                $row = $this->setColumn(
                    $row,
                    $columns,
                    sprintf(static::LOCALIZED_COLUMN_FORMAT, static::COLUMN_PREFIX_NAME, $locale),
                    $data['name'],
                );

                $row = $this->setColumn(
                    $row,
                    $columns,
                    sprintf(static::LOCALIZED_COLUMN_FORMAT, static::COLUMN_PREFIX_DESCRIPTION, $locale),
                    $data['description'],
                );

                if ($data['attributes'] !== '') {
                    $row = $this->setColumn(
                        $row,
                        $columns,
                        sprintf(static::LOCALIZED_COLUMN_FORMAT, static::COLUMN_PREFIX_ATTRIBUTES, $locale),
                        $data['attributes'],
                    );
                }
            }

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, array<string, array<string, string>>>
     */
    protected function buildAbstractLocalizedMap(array $rows): array
    {
        $abstractIdToSkuMap = $this->extractAbstractIdToSkuMap($rows);

        $localizedAttributes = SpyProductAbstractLocalizedAttributesQuery::create()
            ->filterByFkProductAbstract(array_keys($abstractIdToSkuMap), SpyProductAbstractLocalizedAttributesQuery::IN)
            ->joinWithLocale()
            ->withColumn(SpyProductAbstractLocalizedAttributesTableMap::COL_FK_PRODUCT_ABSTRACT, static::ALIAS_FK_PRODUCT_ABSTRACT)
            ->withColumn(SpyProductAbstractLocalizedAttributesTableMap::COL_NAME, static::ALIAS_NAME)
            ->withColumn(SpyProductAbstractLocalizedAttributesTableMap::COL_DESCRIPTION, static::ALIAS_DESCRIPTION)
            ->withColumn(SpyProductAbstractLocalizedAttributesTableMap::COL_ATTRIBUTES, static::ALIAS_ATTRIBUTES)
            ->withColumn(SpyLocaleTableMap::COL_LOCALE_NAME, static::ALIAS_LOCALE_NAME)
            ->select([static::ALIAS_FK_PRODUCT_ABSTRACT, static::ALIAS_NAME, static::ALIAS_DESCRIPTION, static::ALIAS_ATTRIBUTES, static::ALIAS_LOCALE_NAME])
            ->find()
            ->getData();

        $map = [];

        foreach ($localizedAttributes as $localized) {
            $idProductAbstract = $localized[static::ALIAS_FK_PRODUCT_ABSTRACT];

            if (!isset($abstractIdToSkuMap[$idProductAbstract])) {
                continue;
            }

            $abstractSku = $abstractIdToSkuMap[$idProductAbstract];
            $locale = $this->formatLocale($localized[static::ALIAS_LOCALE_NAME]);

            $map[$abstractSku][$locale] = [
                'name' => $localized[static::ALIAS_NAME],
                'description' => $localized[static::ALIAS_DESCRIPTION] ?? '',
                'attributes' => $this->formatAttributes($localized[static::ALIAS_ATTRIBUTES]),
            ];
        }

        return $map;
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, array<string, array<string, string>>>
     */
    protected function buildConcreteLocalizedMap(array $rows): array
    {
        $concreteIdToSkuMap = $this->extractConcreteIdToSkuMap($rows);

        $localizedAttributes = SpyProductLocalizedAttributesQuery::create()
            ->filterByFkProduct(array_keys($concreteIdToSkuMap), SpyProductLocalizedAttributesQuery::IN)
            ->joinWithLocale()
            ->withColumn(SpyProductLocalizedAttributesTableMap::COL_FK_PRODUCT, static::ALIAS_FK_PRODUCT)
            ->withColumn(SpyProductLocalizedAttributesTableMap::COL_NAME, static::ALIAS_NAME)
            ->withColumn(SpyProductLocalizedAttributesTableMap::COL_DESCRIPTION, static::ALIAS_DESCRIPTION)
            ->withColumn(SpyProductLocalizedAttributesTableMap::COL_ATTRIBUTES, static::ALIAS_ATTRIBUTES)
            ->withColumn(SpyLocaleTableMap::COL_LOCALE_NAME, static::ALIAS_LOCALE_NAME)
            ->select([static::ALIAS_FK_PRODUCT, static::ALIAS_NAME, static::ALIAS_DESCRIPTION, static::ALIAS_ATTRIBUTES, static::ALIAS_LOCALE_NAME])
            ->find()
            ->getData();

        $map = [];

        foreach ($localizedAttributes as $localized) {
            $idProduct = $localized[static::ALIAS_FK_PRODUCT];

            if (!isset($concreteIdToSkuMap[$idProduct])) {
                continue;
            }

            $concreteSku = $concreteIdToSkuMap[$idProduct];
            $locale = $this->formatLocale($localized[static::ALIAS_LOCALE_NAME]);

            $map[$concreteSku][$locale] = [
                'name' => $localized[static::ALIAS_NAME],
                'description' => $localized[static::ALIAS_DESCRIPTION] ?? '',
                'attributes' => $this->formatAttributes($localized[static::ALIAS_ATTRIBUTES]),
            ];
        }

        return $map;
    }

    protected function formatAttributes(?string $attributesJson): string
    {
        $attributes = $this->utilEncodingService->decodeJson($attributesJson ?? '', true);

        if (!is_array($attributes) || $attributes === []) {
            return '';
        }

        $attributeParts = [];

        foreach ($attributes as $key => $value) {
            $attributeParts[] = sprintf(static::ATTRIBUTE_FORMAT, $key, is_array($value) ? $this->utilEncodingService->encodeJson($value) : $value);
        }

        return implode(static::SEPARATOR, $attributeParts);
    }
}
