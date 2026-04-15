<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportStepInterface;

abstract class AbstractProductCsvExportStep implements ExportStepInterface
{
    protected const string SEPARATOR = ';';

    protected const string COLUMN_ABSTRACT_SKU = 'Abstract SKU';

    protected const string COLUMN_CONCRETE_SKU = 'Concrete SKU';

    protected const string INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT = '_id_product_abstract';

    protected const string INTERNAL_COLUMN_ID_PRODUCT = '_id_product';

    protected const string ALIAS_ABSTRACT_SKU = 'AbstractSku';

    protected const string ALIAS_CONCRETE_SKU = 'ConcreteSku';

    protected const string ALIAS_LOCALE_NAME = 'LocaleName';

    /**
     * @param array<string, string> $row
     * @param array<string> $columns
     *
     * @return array<string, string>
     */
    protected function setColumn(array $row, array $columns, string $columnName, string $value): array
    {
        if (in_array($columnName, $columns, true)) {
            $row[$columnName] = $value;
        }

        return $row;
    }

    protected function formatLocale(string $localeName): string
    {
        return strtoupper(str_replace('_', '-', $localeName));
    }

    /**
     * @param array<string, string> $row
     */
    protected function isAbstractRow(array $row): bool
    {
        return ($row[static::COLUMN_ABSTRACT_SKU] ?? '') !== '' && ($row[static::COLUMN_CONCRETE_SKU] ?? '') === '';
    }

    /**
     * @param array<string, string> $row
     */
    protected function isConcreteRow(array $row): bool
    {
        return ($row[static::COLUMN_CONCRETE_SKU] ?? '') !== '';
    }

    /**
     * Returns abstract product IDs extracted from rows, without an extra DB query.
     *
     * @param array<int, array<string, string>> $rows
     *
     * @return array<int>
     */
    protected function extractAbstractProductIds(array $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            if ($this->isAbstractRow($row) && isset($row[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT])) {
                $ids[] = (int)$row[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Returns concrete product IDs extracted from rows, without an extra DB query.
     *
     * @param array<int, array<string, string>> $rows
     *
     * @return array<int>
     */
    protected function extractConcreteProductIds(array $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            if ($this->isConcreteRow($row) && isset($row[static::INTERNAL_COLUMN_ID_PRODUCT])) {
                $ids[] = (int)$row[static::INTERNAL_COLUMN_ID_PRODUCT];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Returns a map of abstract product ID to SKU extracted from rows, without an extra DB query.
     *
     * @param array<int, array<string, string>> $rows
     *
     * @return array<int, string>
     */
    protected function extractAbstractIdToSkuMap(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            if ($this->isAbstractRow($row) && isset($row[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT])) {
                $map[(int)$row[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT]] = $row[static::COLUMN_ABSTRACT_SKU];
            }
        }

        return $map;
    }

    /**
     * Returns a map of concrete product ID to SKU extracted from rows, without an extra DB query.
     *
     * @param array<int, array<string, string>> $rows
     *
     * @return array<int, string>
     */
    protected function extractConcreteIdToSkuMap(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            if ($this->isConcreteRow($row) && isset($row[static::INTERNAL_COLUMN_ID_PRODUCT])) {
                $map[(int)$row[static::INTERNAL_COLUMN_ID_PRODUCT]] = $row[static::COLUMN_CONCRETE_SKU];
            }
        }

        return $map;
    }
}
