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
use Orm\Zed\Locale\Persistence\SpyLocaleQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Url\Persistence\SpyUrlQuery;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use Spryker\Zed\Url\Dependency\UrlEvents;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportUrlStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

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

        $processedProductAbstractIds = [];

        foreach ($rows as $rowNumber => $row) {
            if ($this->isConcreteRow($row)) {
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

            $processedProductAbstractIds[] = $this->createOrUpdateUrl($row);
        }

        $this->commit();
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
        $abstractSku = $this->resolveAbstractSku($row);
        $idProductAbstract = $this->resolveProductAbstractId($abstractSku);

        if ($idProductAbstract === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the abstract product does not exist. Expected: an existing abstract product SKU. Please update the value.', $abstractSku, $propertyNamesInFile[static::COLUMN_ABSTRACT_SKU] ?? static::COLUMN_ABSTRACT_SKU)));
        }

        return $result;
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

    protected function resolveLocaleId(string $localeCode): ?int
    {
        if (!isset(static::$localeCache[$localeCode])) {
            static::$localeCache[$localeCode] = SpyLocaleQuery::create()
                ->filterByLocaleName($localeCode)
                ->findOne();
        }

        return static::$localeCache[$localeCode]?->getIdLocale();
    }

    /**
     * @param array<string, string> $row
     */
    protected function createOrUpdateUrl(array $row): ?int
    {
        $abstractSku = $this->resolveAbstractSku($row);
        $idProductAbstract = $this->resolveProductAbstractId($abstractSku);

        $urlColumns = $this->extractLocaleColumns($row, 'url');

        foreach ($urlColumns as $localeCode => $urlValue) {
            $urlValue = trim($urlValue);

            if ($urlValue === '') {
                continue;
            }

            $idLocale = $this->resolveLocaleId($localeCode);

            if ($idLocale === null) {
                continue;
            }

            $urlEntity = SpyUrlQuery::create()
                ->filterByFkResourceProductAbstract($idProductAbstract)
                ->filterByFkLocale($idLocale)
                ->findOneOrCreate();

            $urlEntity->setUrl($urlValue);
            $urlEntity->setFkResourceProductAbstract($idProductAbstract);
            $urlEntity->setFkLocale($idLocale);

            if ($urlEntity->isNew() || $urlEntity->isModified()) {
                $this->persist($urlEntity);
            }
        }

        return $idProductAbstract;
    }

    /**
     * @param array<int|null> $productAbstractIds
     */
    protected function addPublishEvents(array $productAbstractIds, ImportStepResponseTransfer $response): void
    {
        $uniqueAbstractIds = array_unique(array_filter($productAbstractIds));

        foreach ($uniqueAbstractIds as $idProductAbstract) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );
        }

        $urlIds = SpyUrlQuery::create()
            ->filterByFkResourceProductAbstract_In($uniqueAbstractIds)
            ->select(['IdUrl'])
            ->find()
            ->getData();

        foreach ($urlIds as $idUrl) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(UrlEvents::URL_PUBLISH)->setEntityId((int)$idUrl),
            );
        }
    }

    /**
     * Extracts locale-specific values from system property columns like "url.en_us", "url.de_de".
     *
     * @param array<string, string> $row
     *
     * @return array<string, string> locale code (e.g. en_us) => value
     */
    protected function extractLocaleColumns(array $row, string $columnPrefix): array
    {
        $result = [];
        $pattern = sprintf('/^%s\.([a-z]{2}_[a-z]{2})$/', preg_quote($columnPrefix, '/'));

        foreach ($row as $header => $value) {
            if (!preg_match($pattern, $header, $matches)) {
                continue;
            }

            $result[$matches[1]] = $value;
        }

        return $result;
    }
}
