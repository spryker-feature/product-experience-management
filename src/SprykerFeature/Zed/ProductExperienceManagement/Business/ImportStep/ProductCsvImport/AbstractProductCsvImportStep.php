<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportPublishEventTransfer;
use Generated\Shared\Transfer\ImportStepErrorTransfer;
use Spryker\Zed\DataExchangeExtension\Dependency\Plugin\CacheResettableImportStepInterface;
use Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ImportStepInterface;

/**
 * Base class of the product import steps. Implements the engine's step contract, provides the helpers for the two
 * transfers every step reports (a row error and a publish event). Opts every product step into the engine's per-batch
 * cache reset with a no-op `resetCache()`; a step with static lookup caches overrides it.
 */
abstract class AbstractProductCsvImportStep implements ImportStepInterface, CacheResettableImportStepInterface
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string COLUMN_PRODUCT_STATUS = 'product_status';

    protected const string ATTRIBUTE_PAIR_SEPARATOR = ';';

    protected const string ATTRIBUTE_KEY_VALUE_SEPARATOR = '=';

    protected function createImportStepError(int $csvRowNumber, string $errorMessage): ImportStepErrorTransfer
    {
        return (new ImportStepErrorTransfer())
            ->setCsvRowNumber($csvRowNumber)
            ->setErrorMessage($errorMessage);
    }

    protected function createImportPublishEvent(string $eventName, int $entityId): ImportPublishEventTransfer
    {
        return (new ImportPublishEventTransfer())
            ->setEventName($eventName)
            ->setEntityId($entityId);
    }

    /**
     * {@inheritDoc}
     *
     * No-op: a step that keeps lookup caches overrides this and clears them.
     * A step that overrides this method must call the parent method.
     */
    public function resetCache(): void
    {
    }

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
     * @param array<string, string> $row
     */
    protected function resolveProductStatus(array $row): string
    {
        return strtolower(trim($row[static::COLUMN_PRODUCT_STATUS] ?? ''));
    }

    /**
     * @param array<string> $allowedProductStatuses
     */
    protected function formatAllowedProductStatuses(array $allowedProductStatuses): string
    {
        return implode(', ', $allowedProductStatuses);
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
                  $attributes[$key] = str_starts_with($value, '[') ? (json_decode($value, true) ?? $value) : $value;
            }
        }

        return $attributes;
    }
}
