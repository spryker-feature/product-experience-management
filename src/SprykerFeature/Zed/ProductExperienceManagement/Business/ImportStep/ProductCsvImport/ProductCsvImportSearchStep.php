<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportPublishEventTransfer;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\Locale\Persistence\SpyLocaleQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\ProductSearch\Persistence\SpyProductSearchQuery;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\ProductSearch\Dependency\ProductSearchEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

/**
 * Ensures spy_product_search entries exist with is_searchable=1 for all imported concrete products.
 * Required for products to appear in Elasticsearch search results.
 */
class ProductCsvImportSearchStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string PATTERN_NAME_LOCALE = '/^name\.([a-z]{2}_[a-z]{2})$/';

    /**
     * @var array<string, int|null>
     */
    protected static array $productConcreteIdCache = [];

    /**
     * @var array<int>|null
     */
    protected static ?array $localeIds = null;

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $processedProductConcreteIds = [];

        // Track which product IDs had new search entries (vs updates) for correct event type
        $newProductConcreteIds = [];

        foreach ($rows as $row) {
            $concreteSku = $this->resolveConcreteSku($row);

            if ($concreteSku === null) {
                continue;
            }

            $idProduct = $this->resolveProductConcreteId($concreteSku);

            if ($idProduct === null) {
                continue;
            }

            $isNew = $this->ensureSearchEntries($idProduct, $row);
            $processedProductConcreteIds[] = $idProduct;

            if ($isNew) {
                $newProductConcreteIds[] = $idProduct;
            }
        }

        $this->commit();
        $this->addPublishEvents($processedProductConcreteIds, $newProductConcreteIds, $response);

        return $response;
    }

    /**
     * @param array<int> $productConcreteIds
     * @param array<int> $newProductConcreteIds
     */
    protected function addPublishEvents(array $productConcreteIds, array $newProductConcreteIds, ImportStepResponseTransfer $response): void
    {
        $newIds = array_flip($newProductConcreteIds);

        foreach (array_unique($productConcreteIds) as $idProduct) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_CONCRETE_PUBLISH)->setEntityId($idProduct),
            );

            // Emit entity-level search events so ProductConcretePageSearchWritePublisherPlugin indexes in Elasticsearch.
            // Matches the approach used by ProductConcretePropelDataSetWriter in the standard data importer.
            $searchEventName = isset($newIds[$idProduct])
                ? ProductSearchEvents::ENTITY_SPY_PRODUCT_SEARCH_CREATE
                : ProductSearchEvents::ENTITY_SPY_PRODUCT_SEARCH_UPDATE;

            $productSearchIds = SpyProductSearchQuery::create()
                ->filterByFkProduct($idProduct)
                ->select(['IdProductSearch'])
                ->find()
                ->getData();

            foreach ($productSearchIds as $idProductSearch) {
                $response->addPublishEvent(
                    (new ImportPublishEventTransfer())->setEventName($searchEventName)->setEntityId((int)$idProductSearch),
                );
            }
        }
    }

    /**
     * @param array<string, string> $row
     *
     * @return bool Returns true if any search entry was newly created
     */
    protected function ensureSearchEntries(int $idProduct, array $row): bool
    {
        $hasNew = false;

        foreach ($this->resolveLocaleIds($row) as $idLocale) {
            $productSearchEntity = SpyProductSearchQuery::create()
                ->filterByFkProduct($idProduct)
                ->filterByFkLocale($idLocale)
                ->findOneOrCreate();

            if ($productSearchEntity->isNew()) {
                $hasNew = true;
            }

            $productSearchEntity->setIsSearchable(true);

            if ($productSearchEntity->isNew() || $productSearchEntity->isModified()) {
                $this->persist($productSearchEntity);
            }
        }

        return $hasNew;
    }

    /**
     * Extracts locale IDs from the row headers (e.g., "Name (EN-US)" → en_US).
     *
     * @param array<string, string> $row
     *
     * @return array<int>
     */
    protected function resolveLocaleIds(array $row): array
    {
        if (static::$localeIds !== null) {
            return static::$localeIds;
        }

        $localeCodes = [];

        foreach (array_keys($row) as $header) {
            if (preg_match(static::PATTERN_NAME_LOCALE, $header, $matches)) {
                $localeCodes[] = $matches[1];
            }
        }

        if ($localeCodes === []) {
            static::$localeIds = [];

            return static::$localeIds;
        }

        $localeEntities = SpyLocaleQuery::create()
            ->filterByLocaleName_In($localeCodes)
            ->find();

        $rawLocaleIds = array_map(static fn ($locale) => $locale->getIdLocale(), $localeEntities->getArrayCopy());

        static::$localeIds = array_values(array_map(static fn (mixed $id): int => (int)$id, array_filter($rawLocaleIds)));

        return static::$localeIds;
    }

    protected function resolveProductConcreteId(string $concreteSku): ?int
    {
        if (!array_key_exists($concreteSku, static::$productConcreteIdCache)) {
            static::$productConcreteIdCache[$concreteSku] = SpyProductQuery::create()
                ->filterBySku($concreteSku)
                ->findOne()
                ?->getIdProduct();
        }

        return static::$productConcreteIdCache[$concreteSku];
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveConcreteSku(array $row): ?string
    {
        $concreteSku = trim($row[static::COLUMN_CONCRETE_SKU] ?? '');

        return $concreteSku !== '' ? $concreteSku : null;
    }
}
