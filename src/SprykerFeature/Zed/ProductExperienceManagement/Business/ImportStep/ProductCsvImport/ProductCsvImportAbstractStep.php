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
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Tax\Persistence\SpyTaxSetQuery;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportAbstractStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_PRODUCT_STATUS = 'product_status';

    protected const string COLUMN_TAX_SET_NAME = 'tax_set_name';

    protected const string ATTRIBUTE_COLUMN_PATTERN = '/^attributes\.[a-z]{2}_[a-z]{2}$/';

    public function __construct(
        protected UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    /**
     * @var array<string, \Orm\Zed\Product\Persistence\SpyProductAbstract>
     */
    protected static array $productAbstractEntityCache = [];

    /**
     * @var array<string, int|null>
     */
    protected static array $taxSetIdByNameCache = [];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $this->warmUpTaxSetCache();

        $processedSkuList = [];

        foreach ($rows as $rowNumber => $row) {
            if ($this->isProductConcreteRow($row)) {
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

            $processedSkuList[] = $this->upsertProductAbstractEntity($row);
        }

        $this->commit();
        $this->addPublishEvents($processedSkuList, $response);

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile = []): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();
        $taxSetName = $this->resolveTaxSetName($row);

        if ($this->resolveAbstractSku($row) === '') {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'\' in field \'%s\' is not valid because the field is empty. Expected: a non-empty product abstract SKU. Please update the value.', $propertyNamesInFile[static::COLUMN_ABSTRACT_SKU] ?? static::COLUMN_ABSTRACT_SKU)));
        }

        if ($this->resolveProductStatus($row) === '') {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'\' in field \'%s\' is not valid because the field is empty. Expected: a product status (e.g. approved). Please update the value.', $propertyNamesInFile[static::COLUMN_PRODUCT_STATUS] ?? static::COLUMN_PRODUCT_STATUS)));
        }

        if ($taxSetName === '') {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'\' in field \'%s\' is not valid because the field is empty. Expected: an existing tax set name. Please update the value.', $propertyNamesInFile[static::COLUMN_TAX_SET_NAME] ?? static::COLUMN_TAX_SET_NAME)));
        } elseif ($this->resolveTaxSetId($row) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the tax set does not exist. Expected: an existing tax set name. Please update the value.', $taxSetName, $propertyNamesInFile[static::COLUMN_TAX_SET_NAME] ?? static::COLUMN_TAX_SET_NAME)));
        }

        return $result;
    }

    /**
     * @param array<string, string> $row
     */
    protected function isProductConcreteRow(array $row): bool
    {
        return trim($row[static::COLUMN_CONCRETE_SKU] ?? '') !== '';
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveProductStatus(array $row): string
    {
        return trim($row[static::COLUMN_PRODUCT_STATUS] ?? '');
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveTaxSetName(array $row): string
    {
        return trim($row[static::COLUMN_TAX_SET_NAME] ?? '');
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveTaxSetId(array $row): ?int
    {
        return static::$taxSetIdByNameCache[$this->resolveTaxSetName($row)] ?? null;
    }

    /**
     * @param array<string, string> $row
     */
    protected function upsertProductAbstractEntity(array $row): string
    {
        $abstractSku = $this->resolveAbstractSku($row);
        $taxSetId = $this->resolveTaxSetId($row);

        if (!isset(static::$productAbstractEntityCache[$abstractSku])) {
            static::$productAbstractEntityCache[$abstractSku] = SpyProductAbstractQuery::create()
                ->filterBySku($abstractSku)
                ->findOneOrCreate();
        }

        $productAbstractEntity = static::$productAbstractEntityCache[$abstractSku];
        $productAbstractEntity->setSku($abstractSku);
        $productAbstractEntity->setAttributes($this->buildAttributes($row));
        $productAbstractEntity->setApprovalStatus($this->resolveProductStatus($row));

        $productAbstractEntity->setFkTaxSet($taxSetId);

        if ($productAbstractEntity->isNew() || $productAbstractEntity->isModified()) {
            $this->persist($productAbstractEntity);
        }

        return $abstractSku;
    }

    /**
     * @param array<string> $skuList
     */
    protected function addPublishEvents(array $skuList, ImportStepResponseTransfer $response): void
    {
        if ($skuList === []) {
            return;
        }

        $entities = SpyProductAbstractQuery::create()
            ->filterBySku_In(array_unique($skuList))
            ->find();

        foreach ($entities as $entity) {
            $idProductAbstract = $entity->getIdProductAbstract();

            if ($idProductAbstract === null) {
                continue;
            }

            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );
        }
    }

    protected function warmUpTaxSetCache(): void
    {
        if (static::$taxSetIdByNameCache !== []) {
            return;
        }

        $taxSets = SpyTaxSetQuery::create()->find();

        foreach ($taxSets as $taxSetEntity) {
            $taxSetName = $taxSetEntity->getName();

            if ($taxSetName === null) {
                continue;
            }

            static::$taxSetIdByNameCache[$taxSetName] = $taxSetEntity->getIdTaxSet();
        }
    }

    /**
     * @param array<string, string> $row
     */
    protected function buildAttributes(array $row): string
    {
        $attributes = [];

        foreach ($row as $header => $value) {
            if (!preg_match(static::ATTRIBUTE_COLUMN_PATTERN, $header)) {
                continue;
            }

            foreach ($this->parseAttributes($value) as $key => $attrValue) {
                $attributes[$key] = $attrValue;
            }
        }

        return (string)$this->utilEncodingService->encodeJson($attributes);
    }
}
