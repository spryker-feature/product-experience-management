<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportRowValidationCollectionTransfer;
use Generated\Shared\Transfer\ImportStepErrorTransfer;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductQuery;
use Orm\Zed\PriceProduct\Persistence\SpyPriceTypeQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

/**
 * Creates spy_price_product records linking price type to abstract or concrete product.
 * Runs before ProductCsvImportPriceStoreStep which sets the actual amounts.
 */
class ProductCsvImportPriceStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string PRICE_COLUMN_PATTERN = '/^price\.(\w+)\.(\w+)\.(\w+)\.(net|gross)$/';

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

    /**
     * @var array<string, int|null>
     */
    protected static array $productConcreteIdCache = [];

    /**
     * @var array<string, \Orm\Zed\PriceProduct\Persistence\SpyPriceType|null>
     */
    protected static array $priceTypeCache = [];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        foreach ($rows as $rowNumber => $row) {
            $priceTypeNames = $this->parsePriceTypeNames($row);

            if ($priceTypeNames === []) {
                continue;
            }

            $validationResult = $this->validateRow($row, $rowNumber, $propertyNamesInFile);

            if ($validationResult->getErrors()->count() > 0) {
                $response->setIsSuccessful(false);

                foreach ($validationResult->getErrors() as $error) {
                    $response->addError($error);
                }

                continue;
            }

            foreach ($priceTypeNames as $priceTypeName) {
                $this->upsertPriceProduct($priceTypeName, $row);
            }
        }

        $this->commit();

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile = []): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();
        $concreteSku = $this->resolveConcreteSku($row);

        if ($concreteSku !== null) {
            if ($this->resolveProductConcreteId($concreteSku) === null) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the concrete product does not exist. Expected: an existing concrete product SKU. Please update the value.', $concreteSku, $propertyNamesInFile[static::COLUMN_CONCRETE_SKU] ?? static::COLUMN_CONCRETE_SKU)));
            }

            return $result;
        }

        $abstractSku = $this->resolveAbstractSku($row);

        if ($abstractSku === null || $this->resolveProductAbstractId($abstractSku) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the abstract product does not exist. Expected: an existing abstract product SKU. Please update the value.', $abstractSku ?? '', $propertyNamesInFile[static::COLUMN_ABSTRACT_SKU] ?? static::COLUMN_ABSTRACT_SKU)));
        }

        return $result;
    }

    /**
     * @param array<string, string> $row
     */
    protected function upsertPriceProduct(string $priceTypeName, array $row): void
    {
        $this->resolvePriceType($priceTypeName);
        $priceTypeEntity = static::$priceTypeCache[$priceTypeName];

        if ($priceTypeEntity === null) {
            return;
        }

        $idPriceType = $priceTypeEntity->getIdPriceType();

        $concreteSku = $this->resolveConcreteSku($row);

        $query = SpyPriceProductQuery::create()
            ->filterByFkPriceType($idPriceType);

        if ($concreteSku !== null) {
            $query->filterByFkProduct($this->resolveProductConcreteId($concreteSku));
        } else {
            $abstractSku = $this->resolveAbstractSku($row);
            $query->filterByFkProductAbstract($abstractSku !== null ? $this->resolveProductAbstractId($abstractSku) : null);
        }

        $priceProductEntity = $query->findOneOrCreate();

        if ($priceProductEntity->isNew()) {
            $this->persist($priceProductEntity);
        }
    }

    /**
     * Extracts unique price type names from row headers.
     *
     * @param array<string, string> $row
     *
     * @return array<string>
     */
    protected function parsePriceTypeNames(array $row): array
    {
        $types = [];

        foreach ($row as $header => $value) {
            if (!preg_match(static::PRICE_COLUMN_PATTERN, $header, $matches)) {
                continue;
            }

            if (trim($value) === '') {
                continue;
            }

            $types[strtoupper($matches[1])] = true;
        }

        return array_keys($types);
    }

    protected function resolveProductAbstractId(string $abstractSku): ?int
    {
        if (!array_key_exists($abstractSku, static::$productAbstractIdCache)) {
            static::$productAbstractIdCache[$abstractSku] = SpyProductAbstractQuery::create()
                ->filterBySku($abstractSku)
                ->findOne()
                ?->getIdProductAbstract();
        }

        return static::$productAbstractIdCache[$abstractSku];
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

    protected function resolvePriceType(string $priceTypeName): void
    {
        if (!array_key_exists($priceTypeName, static::$priceTypeCache)) {
            static::$priceTypeCache[$priceTypeName] = SpyPriceTypeQuery::create()
                ->filterByName($priceTypeName)
                ->findOne();
        }
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveAbstractSku(array $row): ?string
    {
        $sku = trim($row[static::COLUMN_ABSTRACT_SKU] ?? '');

        return $sku !== '' ? $sku : null;
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveConcreteSku(array $row): ?string
    {
        $sku = trim($row[static::COLUMN_CONCRETE_SKU] ?? '');

        return $sku !== '' ? $sku : null;
    }
}
