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
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig;

class ProductCsvImportConcreteStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_PRODUCT_STATUS = 'product_status';

    protected const string KEY_CONCRETE_SKU = 'concreteSku';

    protected const string KEY_ABSTRACT_SKU = 'abstractSku';

    protected const string CONCRETE_STATUS_ACTIVE = 'active';

    protected const string ATTRIBUTE_COLUMN_PATTERN = '/^attributes\.[a-z]{2}_[a-z]{2}$/';

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

    /**
     * @var array<string, \Orm\Zed\Product\Persistence\SpyProduct>
     */
    protected static array $productConcreteEntityCache = [];

    public function __construct(
        protected ProductExperienceManagementConfig $config,
        protected UtilEncodingServiceInterface $utilEncodingService,
    ) {
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

        $processedSkus = [];

        foreach ($rows as $rowNumber => $row) {
            if (!$this->isConcreteRow($row)) {
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

            $processedSkus[] = $this->upsertProductConcreteEntity($row);
        }

        $this->commit();
        $this->addPublishEvents($processedSkus, $response);

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
        $abstractSku = $this->resolveAbstractSku($row);

        if ($abstractSku === '') {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'\' in field \'%s\' is not valid because the field is empty for concrete SKU \'%s\'. Expected: a non-empty abstract SKU. Please update the value.', $propertyNamesInFile[static::COLUMN_ABSTRACT_SKU] ?? static::COLUMN_ABSTRACT_SKU, $concreteSku)));
        } elseif ($this->resolveProductAbstractId($abstractSku) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the abstract product does not exist. Expected: an existing abstract product SKU. Please update the value.', $abstractSku, $propertyNamesInFile[static::COLUMN_ABSTRACT_SKU] ?? static::COLUMN_ABSTRACT_SKU)));
        }

        return $result;
    }

    /**
     * @param array<array{concreteSku: string, abstractSku: string}> $processedSkus
     */
    protected function addPublishEvents(array $processedSkus, ImportStepResponseTransfer $response): void
    {
        if ($processedSkus === []) {
            return;
        }

        $concreteSkus = array_unique(array_column($processedSkus, static::KEY_CONCRETE_SKU));
        $abstractSkus = array_unique(array_column($processedSkus, static::KEY_ABSTRACT_SKU));

        $concreteEntities = SpyProductQuery::create()
            ->filterBySku_In($concreteSkus)
            ->find();

        foreach ($concreteEntities as $entity) {
            $idProduct = $entity->getIdProduct();

            if ($idProduct === null) {
                continue;
            }

            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_CONCRETE_PUBLISH)->setEntityId($idProduct),
            );
        }

        $abstractEntities = SpyProductAbstractQuery::create()
            ->filterBySku_In($abstractSkus)
            ->find();

        foreach ($abstractEntities as $entity) {
            $idProductAbstract = $entity->getIdProductAbstract();

            if ($idProductAbstract === null) {
                continue;
            }

            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );
        }
    }

    /**
     * @param array<string, string> $row
     *
     * @return array{concreteSku: string, abstractSku: string}
     */
    protected function upsertProductConcreteEntity(array $row): array
    {
        $concreteSku = $this->resolveConcreteSku($row);
        $abstractSku = $this->resolveAbstractSku($row);
        $idProductAbstract = $this->resolveProductAbstractId($abstractSku);

        if (!isset(static::$productConcreteEntityCache[$concreteSku])) {
            static::$productConcreteEntityCache[$concreteSku] = SpyProductQuery::create()
                ->filterBySku($concreteSku)
                ->findOneOrCreate();
        }

        $entity = static::$productConcreteEntityCache[$concreteSku];
        $entity->setSku($concreteSku);
        $entity->setFkProductAbstract((int)$idProductAbstract);
        $entity->setIsActive($this->resolveIsActive($row));
        $entity->setAttributes((string)$this->utilEncodingService->encodeJson($this->buildAttributes($row)));

        if ($entity->isNew() || $entity->isModified()) {
            $this->persist($entity);
        }

        return ['abstractSku' => $abstractSku, 'concreteSku' => $concreteSku];
    }

    protected function resolveProductAbstractId(string $abstractSku): ?int
    {
        if (!array_key_exists($abstractSku, static::$productAbstractIdCache)) {
            $entity = SpyProductAbstractQuery::create()
                ->filterBySku($abstractSku)
                ->findOne();

            static::$productAbstractIdCache[$abstractSku] = $entity?->getIdProductAbstract();
        }

        return static::$productAbstractIdCache[$abstractSku];
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveIsActive(array $row): bool
    {
        $status = trim($row[static::COLUMN_PRODUCT_STATUS] ?? '');

        return strtolower($status) === static::CONCRETE_STATUS_ACTIVE;
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
        return $this->resolveConcreteSku($row);
    }

    /**
     * @param array<string, string> $row
     *
     * @return array<string, string>
     */
    protected function buildAttributes(array $row): array
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

        return $attributes;
    }
}
