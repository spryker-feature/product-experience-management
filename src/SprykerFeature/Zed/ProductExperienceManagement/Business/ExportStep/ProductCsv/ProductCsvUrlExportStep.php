<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Locale\Persistence\Map\SpyLocaleTableMap;
use Orm\Zed\Url\Persistence\Map\SpyUrlTableMap;
use Orm\Zed\Url\Persistence\SpyUrlQuery;

class ProductCsvUrlExportStep extends AbstractProductCsvExportStep
{
    protected const string LOCALIZED_COLUMN_FORMAT = '%s (%s)';

    protected const string COLUMN_PREFIX_URL = 'URL';

    protected const string ALIAS_FK_RESOURCE_PRODUCT_ABSTRACT = 'FkResourceProductAbstract';

    protected const string ALIAS_URL = 'Url';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $urlsByAbstractSku = $this->buildUrlMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($urlsByAbstractSku[$abstractSku])) {
                continue;
            }

            foreach ($urlsByAbstractSku[$abstractSku] as $locale => $url) {
                $columnName = sprintf(static::LOCALIZED_COLUMN_FORMAT, static::COLUMN_PREFIX_URL, $locale);

                $row = $this->setColumn($row, $columns, $columnName, $url);
            }

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, array<string, string>>
     */
    protected function buildUrlMap(array $rows): array
    {
        $abstractIdToSkuMap = $this->extractAbstractIdToSkuMap($rows);

        $urls = SpyUrlQuery::create()
            ->filterByFkResourceProductAbstract(array_keys($abstractIdToSkuMap), SpyUrlQuery::IN)
            ->joinWithSpyLocale()
            ->withColumn(SpyUrlTableMap::COL_FK_RESOURCE_PRODUCT_ABSTRACT, static::ALIAS_FK_RESOURCE_PRODUCT_ABSTRACT)
            ->withColumn(SpyUrlTableMap::COL_URL, static::ALIAS_URL)
            ->withColumn(SpyLocaleTableMap::COL_LOCALE_NAME, static::ALIAS_LOCALE_NAME)
            ->select([static::ALIAS_FK_RESOURCE_PRODUCT_ABSTRACT, static::ALIAS_URL, static::ALIAS_LOCALE_NAME])
            ->find()
            ->getData();

        $urlsByAbstractSku = [];

        foreach ($urls as $urlEntity) {
            $idProductAbstract = $urlEntity[static::ALIAS_FK_RESOURCE_PRODUCT_ABSTRACT];

            if (!isset($abstractIdToSkuMap[$idProductAbstract])) {
                continue;
            }

            $abstractSku = $abstractIdToSkuMap[$idProductAbstract];
            $locale = $this->formatLocale($urlEntity[static::ALIAS_LOCALE_NAME]);

            $urlsByAbstractSku[$abstractSku][$locale] = $urlEntity[static::ALIAS_URL];
        }

        return $urlsByAbstractSku;
    }
}
