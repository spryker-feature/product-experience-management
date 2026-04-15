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
use Orm\Zed\Product\Persistence\SpyProductLocalizedAttributesQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportConcreteLocalizedAttributesStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string PATTERN_NAME_LOCALE = '/^name\.([a-z]{2}_[a-z]{2})$/';

    public function __construct(
        protected UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    /**
     * @var array<string, \Orm\Zed\Product\Persistence\SpyProduct|null>
     */
    protected static array $productConcreteEntityCache = [];

    /**
     * @var array<string, \Orm\Zed\Locale\Persistence\SpyLocale|null>
     */
    protected static array $localeCache = [];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $processedProductIds = [];

        foreach ($rows as $rowNumber => $row) {
            if (!$this->isConcreteRow($row)) {
                continue;
            }

            $concreteSku = $this->resolveConcreteSku($row);
            $idProduct = $this->resolveProductConcreteId($concreteSku);

            if ($idProduct === null) {
                continue;
            }

            $this->upsertLocalizedAttributes($idProduct, $row);
            $processedProductIds[] = $idProduct;
        }

        $this->commit();
        $this->addPublishEvents($processedProductIds, $response);

        return $response;
    }

    protected function resolveProductConcreteId(string $concreteSku): ?int
    {
        if (!array_key_exists($concreteSku, static::$productConcreteEntityCache)) {
            static::$productConcreteEntityCache[$concreteSku] = SpyProductQuery::create()
                ->filterBySku($concreteSku)
                ->findOne();
        }

        return static::$productConcreteEntityCache[$concreteSku]?->getIdProduct();
    }

    /**
     * @param array<string, string> $row
     */
    protected function upsertLocalizedAttributes(int $idProduct, array $row): void
    {
        foreach ($row as $header => $value) {
            if (!preg_match(static::PATTERN_NAME_LOCALE, $header, $matches)) {
                continue;
            }

            $localeCode = $matches[1];

            if (!isset(static::$localeCache[$localeCode])) {
                static::$localeCache[$localeCode] = SpyLocaleQuery::create()
                    ->filterByLocaleName($localeCode)
                    ->findOne();
            }

            $localeEntity = static::$localeCache[$localeCode];

            if ($localeEntity === null) {
                continue;
            }

            $idLocale = $localeEntity->getIdLocale();

            $localizedEntity = SpyProductLocalizedAttributesQuery::create()
                ->filterByFkProduct($idProduct)
                ->filterByFkLocale($idLocale)
                ->findOneOrCreate();

            $localizedEntity->setFkProduct($idProduct);
            $localizedEntity->setFkLocale($idLocale);
            $localizedEntity->setName($value);

            $localizedEntity->setDescription($row[sprintf('description.%s', $localeCode)] ?? '');
            $localizedEntity->setAttributes((string)$this->utilEncodingService->encodeJson($this->parseAttributes($row[sprintf('attributes.%s', $localeCode)] ?? '')));

            $this->persist($localizedEntity);
        }
    }

    /**
     * @param array<int> $productIds
     */
    protected function addPublishEvents(array $productIds, ImportStepResponseTransfer $response): void
    {
        foreach (array_unique($productIds) as $idProduct) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_CONCRETE_PUBLISH)->setEntityId($idProduct),
            );
        }
    }
}
