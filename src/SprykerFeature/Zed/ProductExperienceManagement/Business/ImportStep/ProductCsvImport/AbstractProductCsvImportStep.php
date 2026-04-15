<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

abstract class AbstractProductCsvImportStep
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string ATTRIBUTE_PAIR_SEPARATOR = ';';

    protected const string ATTRIBUTE_KEY_VALUE_SEPARATOR = '=';

    /**
     * @param array<string, string> $row
     */
    protected function isConcreteRow(array $row): bool
    {
        return trim($row[static::COLUMN_CONCRETE_SKU] ?? '') !== '';
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveAbstractSku(array $row): string
    {
        return trim($row[static::COLUMN_ABSTRACT_SKU] ?? '');
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveConcreteSku(array $row): string
    {
        return trim($row[static::COLUMN_CONCRETE_SKU] ?? '');
    }

    /**
     * Parses semicolon-separated key=value attributes.
     *
     * @return array<string, string>
     */
    protected function parseAttributes(string $attributeString): array
    {
        $attributes = [];

        if ($attributeString === '') {
            return $attributes;
        }

        $pairSeparator = static::ATTRIBUTE_PAIR_SEPARATOR;
        $keyValueSeparator = static::ATTRIBUTE_KEY_VALUE_SEPARATOR;
        assert($pairSeparator !== '');
        assert($keyValueSeparator !== '');

        foreach (explode($pairSeparator, $attributeString) as $pair) {
            $pair = trim($pair);

            if ($pair === '' || !str_contains($pair, $keyValueSeparator)) {
                continue;
            }

            [$key, $value] = explode($keyValueSeparator, $pair, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key !== '') {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }
}
