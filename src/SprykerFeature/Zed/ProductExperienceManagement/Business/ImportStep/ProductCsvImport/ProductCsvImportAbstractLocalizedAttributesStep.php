<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\Locale\Persistence\SpyLocaleQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractLocalizedAttributesQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportAbstractLocalizedAttributesStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    public function __construct(
        protected UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

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

        foreach ($rows as $row) {
            if ($this->isConcreteRow($row)) {
                continue;
            }

            $abstractSku = $this->resolveAbstractSku($row);
            $idProductAbstract = $this->resolveProductAbstractId($abstractSku);

            if ($idProductAbstract === null) {
                continue;
            }

            $this->upsertLocalizedAttributes($idProductAbstract, $row);
        }

        $this->commit();

        return $response;
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
     * @param array<string, string> $row
     */
    protected function upsertLocalizedAttributes(int $idProductAbstract, array $row): void
    {
        $localeColumns = $this->extractLocaleColumns($row, 'name');

        foreach ($localeColumns as $localeCode => $name) {
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

            $localizedEntity = SpyProductAbstractLocalizedAttributesQuery::create()
                ->filterByFkProductAbstract($idProductAbstract)
                ->filterByFkLocale($idLocale)
                ->findOneOrCreate();

            $localizedEntity->setFkProductAbstract($idProductAbstract);
            $localizedEntity->setFkLocale($idLocale);
            $localizedEntity->setName($name);

            $localizedEntity->setDescription($row[sprintf('description.%s', $localeCode)] ?? '');
            $localizedEntity->setMetaTitle($row[sprintf('meta_title.%s', $localeCode)] ?? '');
            $localizedEntity->setMetaDescription($row[sprintf('meta_description.%s', $localeCode)] ?? '');
            $localizedEntity->setMetaKeywords($row[sprintf('meta_keywords.%s', $localeCode)] ?? '');
            $localizedEntity->setAttributes((string)$this->utilEncodingService->encodeJson($this->parseAttributes($row[sprintf('attributes.%s', $localeCode)] ?? '')));

            $this->persist($localizedEntity);
        }
    }

    /**
     * Extracts locale-specific values from system property columns like "name.en_us", "name.de_de".
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
