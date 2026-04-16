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
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\ProductImage\Persistence\SpyProductImageQuery;
use Orm\Zed\ProductImage\Persistence\SpyProductImageSetQuery;
use Orm\Zed\ProductImage\Persistence\SpyProductImageSetToProductImageQuery;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\ProductImage\Dependency\ProductImageEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

/**
 * Processes image columns: "Images {size} ({locale}-{sort_order})" → system "images.{size}.{locale}.{sort_order}".
 * Each column contains a single image URL.
 * Handles both abstract and concrete product images.
 */
class ProductCsvImportImageStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string IMAGE_SET_NAME = 'default';

    protected const string DEFAULT_LOCALE_KEY = 'default';

    protected const string IMAGE_SIZE_SMALL = 'small';

    protected const string IMAGE_SIZE_LARGE = 'large';

    // Matches system property names like images.small.en_us.1 or images.large.default.1
    protected const string IMAGE_COLUMN_PATTERN = '/^images\.(\w+)\.(.+)\.(\d+)$/';

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

    /**
     * @var array<string, int|null>
     */
    protected static array $productConcreteIdCache = [];

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
        $processedProductConcreteIds = [];

        foreach ($rows as $rowNumber => $row) {
            $imageGroups = $this->parseImageGroups($row);

            if ($imageGroups === []) {
                continue;
            }

            $validationResult = $this->validateRow($row, $rowNumber, $imageGroups);

            if ($validationResult->getErrors()->count() > 0) {
                $response->setIsSuccessful(false);

                foreach ($validationResult->getErrors() as $error) {
                    $response->addError($error);
                }

                continue;
            }

            if ($this->isConcreteRow($row)) {
                $concreteSku = $this->resolveConcreteSku($row);
                $processedProductConcreteIds[] = $this->resolveProductConcreteId($concreteSku);
            }

            $processedProductAbstractIds[] = $this->upsertImagesFromRow($row, $imageGroups);
        }

        $this->commit();
        $this->addPublishEvents($processedProductAbstractIds, $processedProductConcreteIds, $response);

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, array<array{urlSmall: string, urlLarge: string, sortOrder: int}>> $imageGroups
     */
    protected function validateRow(array $row, int $rowNumber, array $imageGroups): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();

        if ($this->isConcreteRow($row)) {
            $this->validateConcreteRow($row, $rowNumber, $result);
        } else {
            $this->validateAbstractRow($row, $rowNumber, $result);
        }

        $this->validateImageUrls($imageGroups, $rowNumber, $result);

        return $result;
    }

    /**
     * @param array<string, string> $row
     */
    protected function validateAbstractRow(array $row, int $rowNumber, ImportRowValidationCollectionTransfer $result): void
    {
        $abstractSku = $this->resolveAbstractSku($row);

        if ($abstractSku === '' || $this->resolveProductAbstractId($abstractSku) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'Abstract SKU\' is not valid because the abstract product does not exist. Cannot assign images.', $abstractSku)));
        }
    }

    /**
     * @param array<string, string> $row
     */
    protected function validateConcreteRow(array $row, int $rowNumber, ImportRowValidationCollectionTransfer $result): void
    {
        $concreteSku = $this->resolveConcreteSku($row);

        if ($concreteSku === '' || $this->resolveProductConcreteId($concreteSku) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'Concrete SKU\' is not valid because the concrete product does not exist. Cannot assign images.', $concreteSku)));
        }
    }

    /**
     * @param array<string, array<array{urlSmall: string, urlLarge: string, sortOrder: int}>> $imageGroups
     */
    protected function validateImageUrls(array $imageGroups, int $rowNumber, ImportRowValidationCollectionTransfer $result): void
    {
        foreach ($imageGroups as $localeKey => $entries) {
            if (!$this->isDefaultLocale($localeKey) && $this->resolveLocaleId($localeKey) === null) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf('The locale \'%s\' referenced in image columns does not exist in the system.', $localeKey)));
            }

            foreach ($entries as $entry) {
                if ($entry['urlLarge'] !== '' && !$this->isValidImageUrl($entry['urlLarge'])) {
                    $result->addError((new ImportStepErrorTransfer())
                        ->setCsvRowNumber($rowNumber)
                        ->setErrorMessage(sprintf('The image URL \'%s\' is not a valid URL.', $entry['urlLarge'])));
                }

                if ($entry['urlSmall'] !== '' && !$this->isValidImageUrl($entry['urlSmall'])) {
                    $result->addError((new ImportStepErrorTransfer())
                        ->setCsvRowNumber($rowNumber)
                        ->setErrorMessage(sprintf('The image URL \'%s\' is not a valid URL.', $entry['urlSmall'])));
                }
            }
        }
    }

    /**
     * @param array<string, string> $row
     * @param array<string, array<array{urlSmall: string, urlLarge: string, sortOrder: int}>> $imageGroups
     */
    protected function upsertImagesFromRow(array $row, array $imageGroups): ?int
    {
        if ($this->isConcreteRow($row)) {
            return $this->upsertConcreteImagesFromRow($row, $imageGroups);
        }

        return $this->upsertAbstractImagesFromRow($row, $imageGroups);
    }

    /**
     * @param array<string, string> $row
     * @param array<string, array<array{urlSmall: string, urlLarge: string, sortOrder: int}>> $imageGroups
     */
    protected function upsertAbstractImagesFromRow(array $row, array $imageGroups): ?int
    {
        $abstractSku = $this->resolveAbstractSku($row);
        $idProductAbstract = $this->resolveProductAbstractId($abstractSku);

        if ($idProductAbstract === null) {
            return null;
        }

        foreach ($imageGroups as $localeKey => $imageEntries) {
            $idLocale = $this->isDefaultLocale($localeKey) ? null : $this->resolveLocaleId($localeKey);

            if (!$this->isDefaultLocale($localeKey) && $idLocale === null) {
                continue;
            }

            $this->upsertImageSet($imageEntries, $idProductAbstract, null, $idLocale);
        }

        return $idProductAbstract;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, array<array{urlSmall: string, urlLarge: string, sortOrder: int}>> $imageGroups
     */
    protected function upsertConcreteImagesFromRow(array $row, array $imageGroups): ?int
    {
        $concreteSku = $this->resolveConcreteSku($row);
        $idProduct = $this->resolveProductConcreteId($concreteSku);

        if ($idProduct === null) {
            return null;
        }

        // Resolve abstract ID for publish event
        $idProductAbstract = $this->resolveProductAbstractIdByConcreteId($idProduct);

        foreach ($imageGroups as $localeKey => $imageEntries) {
            $idLocale = $this->isDefaultLocale($localeKey) ? null : $this->resolveLocaleId($localeKey);

            if (!$this->isDefaultLocale($localeKey) && $idLocale === null) {
                continue;
            }

            $this->upsertImageSet($imageEntries, null, $idProduct, $idLocale);
        }

        return $idProductAbstract;
    }

    /**
     * Accepts absolute URLs (http/https) and relative paths starting with '/'.
     * Relative paths are valid for locally hosted images.
     */
    protected function isValidImageUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }

        return (bool)filter_var(str_replace(' ', '%20', $url), FILTER_VALIDATE_URL);
    }

    /**
     * @param array<string, string> $row
     *
     * @return array<string, array<array{urlSmall: string, urlLarge: string, sortOrder: int}>>
     */
    protected function parseImageGroups(array $row): array
    {
        $rawEntries = [];

        foreach ($row as $key => $value) {
            if (!preg_match(static::IMAGE_COLUMN_PATTERN, $key, $matches)) {
                continue;
            }

            $value = trim($value);

            if ($value === '') {
                continue;
            }

            $size = $matches[1];
            $localeKey = $matches[2];
            $sortOrder = (int)$matches[3];

            $groupKey = sprintf('%s_%d', $localeKey, $sortOrder);

            if (!isset($rawEntries[$localeKey][$groupKey])) {
                $rawEntries[$localeKey][$groupKey] = [
                    'urlSmall' => '',
                    'urlLarge' => '',
                    'sortOrder' => $sortOrder,
                ];
            }

            if ($size === static::IMAGE_SIZE_SMALL) {
                $rawEntries[$localeKey][$groupKey]['urlSmall'] = $value;
            }

            if ($size === static::IMAGE_SIZE_LARGE) {
                $rawEntries[$localeKey][$groupKey]['urlLarge'] = $value;
            }
        }

        // Filter out entries with no URLs
        $imageGroups = [];

        foreach ($rawEntries as $localeKey => $entries) {
            $filtered = array_filter(
                array_values($entries),
                static fn (array $entry): bool => $entry['urlSmall'] !== '' || $entry['urlLarge'] !== '',
            );

            if ($filtered !== []) {
                $imageGroups[$localeKey] = $filtered;
            }
        }

        return $imageGroups;
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

    protected function resolveProductAbstractIdByConcreteId(int $idProduct): ?int
    {
        $entity = SpyProductQuery::create()
            ->filterByIdProduct($idProduct)
            ->findOne();

        return $entity?->getFkProductAbstract();
    }

    protected function resolveLocaleId(string $localeKey): ?int
    {
        if (!isset(static::$localeCache[$localeKey])) {
            static::$localeCache[$localeKey] = SpyLocaleQuery::create()
                ->filterByLocaleName($localeKey)
                ->findOne();
        }

        return static::$localeCache[$localeKey]?->getIdLocale();
    }

    protected function isDefaultLocale(string $localeKey): bool
    {
        return $localeKey === static::DEFAULT_LOCALE_KEY;
    }

    /**
     * @param array<array{urlSmall: string, urlLarge: string, sortOrder: int}> $imageEntries
     */
    protected function upsertImageSet(array $imageEntries, ?int $idProductAbstract, ?int $idProduct, ?int $idLocale): void
    {
        $imageSetEntity = SpyProductImageSetQuery::create()
            ->filterByName(static::IMAGE_SET_NAME)
            ->filterByFkProductAbstract($idProductAbstract)
            ->filterByFkProduct($idProduct)
            ->filterByFkLocale($idLocale)
            ->findOneOrCreate();

        if ($imageSetEntity->isNew() || $imageSetEntity->isModified()) {
            $imageSetEntity->save();
        }

        foreach ($imageEntries as $entry) {
            $urlLarge = $entry['urlLarge'] !== '' ? $entry['urlLarge'] : $entry['urlSmall'];
            $urlSmall = $entry['urlSmall'] !== '' ? $entry['urlSmall'] : $entry['urlLarge'];

            $imageEntity = SpyProductImageQuery::create()
                ->filterByExternalUrlLarge($urlLarge)
                ->filterByExternalUrlSmall($urlSmall)
                ->findOneOrCreate();

            $imageEntity->setExternalUrlLarge($urlLarge);
            $imageEntity->setExternalUrlSmall($urlSmall);

            if ($imageEntity->isNew() || $imageEntity->isModified()) {
                $imageEntity->save();
            }

            $idProductImageSet = $imageSetEntity->getIdProductImageSet();
            $idProductImage = $imageEntity->getIdProductImage();

            $relationEntity = SpyProductImageSetToProductImageQuery::create()
                ->filterByFkProductImageSet($idProductImageSet)
                ->filterByFkProductImage($idProductImage)
                ->findOneOrCreate();

            $relationEntity->setSortOrder($entry['sortOrder']);

            if ($relationEntity->isNew() || $relationEntity->isModified()) {
                $relationEntity->save();
            }
        }
    }

    /**
     * @param array<int|null> $productAbstractIds
     * @param array<int|null> $productConcreteIds
     */
    protected function addPublishEvents(array $productAbstractIds, array $productConcreteIds, ImportStepResponseTransfer $response): void
    {
        foreach (array_unique(array_filter($productAbstractIds)) as $idProductAbstract) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );

            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductImageEvents::PRODUCT_IMAGE_PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );
        }

        foreach (array_unique(array_filter($productConcreteIds)) as $idProduct) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductImageEvents::PRODUCT_IMAGE_PRODUCT_CONCRETE_PUBLISH)->setEntityId($idProduct),
            );
        }
    }
}
