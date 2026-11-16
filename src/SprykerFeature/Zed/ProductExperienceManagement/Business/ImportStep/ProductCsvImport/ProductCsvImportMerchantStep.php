<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportPublishEventTransfer;
use Generated\Shared\Transfer\ImportRowValidationCollectionTransfer;
use Generated\Shared\Transfer\ImportStepErrorTransfer;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\Merchant\Persistence\SpyMerchantQuery;
use Orm\Zed\MerchantProduct\Persistence\SpyMerchantProductAbstractQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;

class ProductCsvImportMerchantStep extends AbstractProductCsvImportStep
{
    use ActiveRecordBatchProcessorTrait;
    use TransactionTrait;

    protected const string COLUMN_MERCHANT = 'merchant';

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

    /**
     * @var array<string, \Orm\Zed\Merchant\Persistence\SpyMerchant|null>
     */
    protected static array $merchantCache = [];

    /**
     * {@inheritDoc}
     */
    public function resetCache(): void
    {
        static::$productAbstractIdCache = [];
        static::$merchantCache = [];
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $rowsByAbstractSku = [];

        foreach ($rows as $rowNumber => $row) {
            if ($this->isConcreteRow($row) || !$this->hasMerchantColumn($row)) {
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

            $rowsByAbstractSku[$this->resolveAbstractSku($row)] = $row;
        }

        $processedProductAbstractIds = array_map($this->assignMerchant(...), array_values($rowsByAbstractSku));

        $this->getTransactionHandler()->handleTransaction(fn (): bool => $this->commit());
        $this->addPublishEvents($processedProductAbstractIds, $response);

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile = []): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();
        $merchantReference = $this->resolveMerchantReference($row);

        if ($merchantReference === '') {
            return $result;
        }

        if (!array_key_exists($merchantReference, static::$merchantCache)) {
            static::$merchantCache[$merchantReference] = SpyMerchantQuery::create()
                ->filterByMerchantReference($merchantReference)
                ->_or()
                ->filterByName($merchantReference)
                ->findOne();
        }

        if (static::$merchantCache[$merchantReference] === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the merchant does not exist. Expected: an existing merchant reference or name. Please update the value.', $merchantReference, $propertyNamesInFile[static::COLUMN_MERCHANT] ?? static::COLUMN_MERCHANT)));
        }

        return $result;
    }

    /**
     * @param array<string, string> $row
     */
    protected function assignMerchant(array $row): ?int
    {
        $idProductAbstract = $this->resolveProductAbstractId($this->resolveAbstractSku($row));

        if ($idProductAbstract === null) {
            return null;
        }

        $merchantReference = $this->resolveMerchantReference($row);

        if ($merchantReference === '') {
            return $this->unassignMerchant($idProductAbstract);
        }

        $merchantEntity = static::$merchantCache[$merchantReference] ?? null;

        if ($merchantEntity === null) {
            return null;
        }

        $merchantProductEntity = SpyMerchantProductAbstractQuery::create()
            ->filterByFkProductAbstract($idProductAbstract)
            ->findOneOrCreate();

        $merchantProductEntity->setFkMerchant($merchantEntity->getIdMerchant());

        $this->persist($merchantProductEntity);

        return $idProductAbstract;
    }

    protected function unassignMerchant(int $idProductAbstract): ?int
    {
        $merchantProductEntity = SpyMerchantProductAbstractQuery::create()
            ->filterByFkProductAbstract($idProductAbstract)
            ->findOne();

        if ($merchantProductEntity === null) {
            return null;
        }

        $this->remove($merchantProductEntity);

        return $idProductAbstract;
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

    /**
     * @param array<int|null> $productAbstractIds
     */
    protected function addPublishEvents(array $productAbstractIds, ImportStepResponseTransfer $response): void
    {
        foreach (array_unique(array_filter($productAbstractIds)) as $idProductAbstract) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );
        }
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveAbstractSku(array $row): string
    {
        $abstractSku = trim($row[static::COLUMN_ABSTRACT_SKU] ?? '');

        if ($abstractSku !== '') {
            return $abstractSku;
        }

        // 1 abstract + 1 concrete: same SKU for both
        return trim($row[static::COLUMN_CONCRETE_SKU] ?? '');
    }

    /**
     * @param array<string, string> $row
     */
    protected function hasMerchantColumn(array $row): bool
    {
        return array_key_exists(static::COLUMN_MERCHANT, $row);
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveMerchantReference(array $row): string
    {
        return trim($row[static::COLUMN_MERCHANT] ?? '');
    }
}
