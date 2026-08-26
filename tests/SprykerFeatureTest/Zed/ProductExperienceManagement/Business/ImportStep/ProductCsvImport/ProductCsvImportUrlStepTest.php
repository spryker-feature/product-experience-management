<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Orm\Zed\Locale\Persistence\SpyLocale;
use Orm\Zed\Locale\Persistence\SpyLocaleQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstract;
use Orm\Zed\Product\Persistence\SpyProductAbstractLocalizedAttributes;
use Orm\Zed\Url\Persistence\SpyUrl;
use Orm\Zed\Url\Persistence\SpyUrlQuery;
use ReflectionClass;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportUrlStep;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group ProductCsvImportUrlStepTest
 */
class ProductCsvImportUrlStepTest extends Unit
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_URL_DE = 'url.de_de';

    protected const string COLUMN_URL_EN = 'url.en_us';

    protected const string LOCALE_NAME_DE = 'de_DE';

    protected const string LOCALE_NAME_EN = 'en_US';

    protected const string CSV_LOCALE_NAME_DE = 'de_de';

    protected const string PRODUCT_ABSTRACT_NAME = 'Url Step Test Product';

    protected const string PRODUCT_ABSTRACT_NAME_SECOND = 'Url Step Test Second Product';

    protected const string URL_FROM_FILE = '/de-de/url-step-test-from-file';

    protected const string URL_ALREADY_STORED = '/de-de/url-step-test-already-stored';

    protected const string GENERATED_URL_PATTERN = '/%s/%s-%d';

    protected const string MODULE_NAME_PRODUCT = 'Product';

    protected const string CONFIG_METHOD_FULL_LOCALE_NAMES_IN_URL = 'isFullLocaleNamesInUrlEnabled';

    protected const string LOCALE_NAME_SEPARATOR = '_';

    protected const string URL_LOCALE_SEPARATOR = '-';

    protected const string GENERATED_URL_SLUG = 'url-step-test-product';

    protected const string GENERATED_URL_SLUG_SECOND = 'url-step-test-second-product';

    protected const int CSV_ROW_NUMBER = 2;

    protected const string CACHE_PROPERTY_LOCALE = 'localeCache';

    protected const string CACHE_PROPERTY_PRODUCT_ABSTRACT_ID = 'productAbstractIdCache';

    /**
     * @var array<\Generated\Shared\Transfer\ProductAbstractTransfer>
     */
    protected array $generatedForProductAbstractTransfers = [];

    protected ProductExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedForProductAbstractTransfers = [];
        $this->tester->setDependency(
            ProductExperienceManagementDependencyProvider::FACADE_PRODUCT,
            $this->createProductFacadeMock(),
        );

        $this->resetImportStepCaches();
        $this->getLocaleEntity(static::CSV_LOCALE_NAME_DE);
    }

    public function testStoresUrlFromFileWhenProductAbstractHasNoUrlYet(): void
    {
        // Arrange
        $productAbstractEntity = $this->createProductAbstractEntity('url-step-test-new-with-url');

        // Act
        $this->executeStep($productAbstractEntity, [static::COLUMN_URL_DE => static::URL_FROM_FILE]);

        // Assert
        $this->assertSame(static::URL_FROM_FILE, $this->findStoredUrl($productAbstractEntity, static::CSV_LOCALE_NAME_DE));
        $this->assertSame([], $this->generatedForProductAbstractTransfers);
    }

    /**
     * @dataProvider provideFullLocaleNamesInUrlFlags
     */
    public function testGeneratesUrlWhenProductAbstractHasNoUrlAndFileHasNone(bool $isFullLocaleNamesInUrlEnabled): void
    {
        // Arrange
        $this->mockFullLocaleNamesInUrl($isFullLocaleNamesInUrlEnabled);
        $productAbstractEntity = $this->createProductAbstractEntity('url-step-test-new-without-url');

        // Act
        $this->executeStep($productAbstractEntity, [static::COLUMN_URL_DE => '']);

        // Assert
        $this->assertCount(1, $this->generatedForProductAbstractTransfers);
        $this->assertSame(
            $productAbstractEntity->getIdProductAbstract(),
            $this->generatedForProductAbstractTransfers[0]->getIdProductAbstract(),
        );
        $this->assertSame($productAbstractEntity->getSku(), $this->generatedForProductAbstractTransfers[0]->getSku());
        $this->assertSame(
            static::PRODUCT_ABSTRACT_NAME,
            $this->generatedForProductAbstractTransfers[0]->getLocalizedAttributes()->offsetGet(0)->getName(),
        );
        $this->assertGeneratedUrlIsStored($productAbstractEntity, static::GENERATED_URL_SLUG, $isFullLocaleNamesInUrlEnabled);
    }

    /**
     * @dataProvider provideFullLocaleNamesInUrlFlags
     */
    public function testGeneratesUrlForEveryProductAbstractWithoutUrlInTheSameBatch(bool $isFullLocaleNamesInUrlEnabled): void
    {
        // Arrange
        $this->mockFullLocaleNamesInUrl($isFullLocaleNamesInUrlEnabled);
        $firstProductAbstractEntity = $this->createProductAbstractEntity('url-step-test-batch-first');
        $secondProductAbstractEntity = $this->createProductAbstractEntity(
            'url-step-test-batch-second',
            static::PRODUCT_ABSTRACT_NAME_SECOND,
        );

        // Act
        $this->executeStepForRows([
            static::CSV_ROW_NUMBER => [
                static::COLUMN_ABSTRACT_SKU => $firstProductAbstractEntity->getSku(),
                static::COLUMN_URL_DE => '',
            ],
            static::CSV_ROW_NUMBER + 1 => [
                static::COLUMN_ABSTRACT_SKU => $secondProductAbstractEntity->getSku(),
                static::COLUMN_URL_DE => '',
            ],
        ]);

        // Assert
        $this->assertCount(2, $this->generatedForProductAbstractTransfers);
        $this->assertGeneratedUrlIsStored($firstProductAbstractEntity, static::GENERATED_URL_SLUG, $isFullLocaleNamesInUrlEnabled);
        $this->assertGeneratedUrlIsStored($secondProductAbstractEntity, static::GENERATED_URL_SLUG_SECOND, $isFullLocaleNamesInUrlEnabled);
        $this->assertSame(
            $firstProductAbstractEntity->getSku(),
            $this->findGeneratedForProductAbstractTransfer($firstProductAbstractEntity)?->getSku(),
        );
        $this->assertSame(
            $secondProductAbstractEntity->getSku(),
            $this->findGeneratedForProductAbstractTransfer($secondProductAbstractEntity)?->getSku(),
        );
    }

    /**
     * @dataProvider provideFullLocaleNamesInUrlFlags
     */
    public function testGeneratesUrlOnlyForProductAbstractWithoutUrlWhenBatchAlsoContainsUrlFromFile(bool $isFullLocaleNamesInUrlEnabled): void
    {
        // Arrange
        $this->mockFullLocaleNamesInUrl($isFullLocaleNamesInUrlEnabled);
        $productAbstractEntityWithUrl = $this->createProductAbstractEntity('url-step-test-batch-with-url');
        $productAbstractEntityWithoutUrl = $this->createProductAbstractEntity(
            'url-step-test-batch-without-url',
            static::PRODUCT_ABSTRACT_NAME_SECOND,
        );

        // Act
        $this->executeStepForRows([
            static::CSV_ROW_NUMBER => [
                static::COLUMN_ABSTRACT_SKU => $productAbstractEntityWithUrl->getSku(),
                static::COLUMN_URL_DE => static::URL_FROM_FILE,
            ],
            static::CSV_ROW_NUMBER + 1 => [
                static::COLUMN_ABSTRACT_SKU => $productAbstractEntityWithoutUrl->getSku(),
                static::COLUMN_URL_DE => '',
            ],
        ]);

        // Assert
        $this->assertCount(1, $this->generatedForProductAbstractTransfers);
        $this->assertSame(static::URL_FROM_FILE, $this->findStoredUrl($productAbstractEntityWithUrl, static::CSV_LOCALE_NAME_DE));
        $this->assertGeneratedUrlIsStored($productAbstractEntityWithoutUrl, static::GENERATED_URL_SLUG_SECOND, $isFullLocaleNamesInUrlEnabled);
        $this->assertSame(
            $productAbstractEntityWithoutUrl->getSku(),
            $this->generatedForProductAbstractTransfers[0]->getSku(),
        );
    }

    /**
     * @return array<string, array{bool}>
     */
    public function provideFullLocaleNamesInUrlFlags(): array
    {
        return [
            'full locale names in URL enabled' => [true],
            'full locale names in URL disabled' => [false],
        ];
    }

    public function testKeepsStoredUrlWhenFileHasNoUrl(): void
    {
        // Arrange
        $productAbstractEntity = $this->createProductAbstractEntity('url-step-test-existing-without-url');
        $this->createUrlEntity($productAbstractEntity, static::CSV_LOCALE_NAME_DE, static::URL_ALREADY_STORED);

        // Act
        $this->executeStep($productAbstractEntity, [static::COLUMN_URL_DE => '']);

        // Assert
        $this->assertSame(static::URL_ALREADY_STORED, $this->findStoredUrl($productAbstractEntity, static::CSV_LOCALE_NAME_DE));
        $this->assertSame([], $this->generatedForProductAbstractTransfers);
    }

    public function testUpdatesStoredUrlWhenFileHasUrl(): void
    {
        // Arrange
        $productAbstractEntity = $this->createProductAbstractEntity('url-step-test-existing-with-url');
        $urlEntity = $this->createUrlEntity($productAbstractEntity, static::CSV_LOCALE_NAME_DE, static::URL_ALREADY_STORED);

        // Act
        $this->executeStep($productAbstractEntity, [static::COLUMN_URL_DE => static::URL_FROM_FILE]);

        // Assert
        $this->assertSame(static::URL_FROM_FILE, $this->findStoredUrl($productAbstractEntity, static::CSV_LOCALE_NAME_DE));
        $this->assertCount(1, SpyUrlQuery::create()->filterByFkResourceProductAbstract($productAbstractEntity->getIdProductAbstract())->find());
        $this->assertSame($urlEntity->getIdUrl(), $this->findStoredUrlEntity($productAbstractEntity, static::CSV_LOCALE_NAME_DE)?->getIdUrl());
        $this->assertSame([], $this->generatedForProductAbstractTransfers);
    }

    public function testDoesNotGenerateUrlForLocaleMissingFromFileWhenAnotherLocaleIsFilled(): void
    {
        // Arrange
        $productAbstractEntity = $this->createProductAbstractEntity('url-step-test-partial-locales');

        // Act
        $this->executeStep($productAbstractEntity, [
            static::COLUMN_URL_DE => static::URL_FROM_FILE,
            static::COLUMN_URL_EN => '',
        ]);

        // Assert
        $this->assertSame(static::URL_FROM_FILE, $this->findStoredUrl($productAbstractEntity, static::CSV_LOCALE_NAME_DE));
        $this->assertNull($this->findStoredUrl($productAbstractEntity, static::LOCALE_NAME_EN));
        $this->assertSame([], $this->generatedForProductAbstractTransfers);
    }

    /**
     * @param array<string, string> $urlColumns
     */
    protected function executeStep(SpyProductAbstract $productAbstractEntity, array $urlColumns): void
    {
        $this->executeStepForRows([
            static::CSV_ROW_NUMBER => [static::COLUMN_ABSTRACT_SKU => $productAbstractEntity->getSku()] + $urlColumns,
        ]);
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    protected function executeStepForRows(array $rows): void
    {
        /** @var \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory $factory */
        $factory = $this->tester->getFactory();

        $factory->createProductCsvImportUrlStep()->executeBatch($rows);
    }

    protected function mockFullLocaleNamesInUrl(bool $isFullLocaleNamesInUrlEnabled): void
    {
        $this->tester->mockConfigMethod(
            static::CONFIG_METHOD_FULL_LOCALE_NAMES_IN_URL,
            $isFullLocaleNamesInUrlEnabled,
            static::MODULE_NAME_PRODUCT,
        );
    }

    protected function assertGeneratedUrlIsStored(
        SpyProductAbstract $productAbstractEntity,
        string $expectedUrlSlug,
        bool $isFullLocaleNamesInUrlEnabled,
    ): void {
        $this->assertSame(
            sprintf(
                static::GENERATED_URL_PATTERN,
                $this->getGeneratedUrlLocalePrefix(static::LOCALE_NAME_DE, $isFullLocaleNamesInUrlEnabled),
                $expectedUrlSlug,
                $productAbstractEntity->getIdProductAbstract(),
            ),
            $this->findStoredUrl($productAbstractEntity, static::LOCALE_NAME_DE),
        );
    }

    protected function getGeneratedUrlLocalePrefix(string $localeName, bool $isFullLocaleNamesInUrlEnabled): string
    {
        if (!$isFullLocaleNamesInUrlEnabled) {
            return mb_substr($localeName, 0, 2);
        }

        return str_replace(static::LOCALE_NAME_SEPARATOR, static::URL_LOCALE_SEPARATOR, strtolower($localeName));
    }

    protected function findGeneratedForProductAbstractTransfer(SpyProductAbstract $productAbstractEntity): ?ProductAbstractTransfer
    {
        foreach ($this->generatedForProductAbstractTransfers as $productAbstractTransfer) {
            if ($productAbstractTransfer->getIdProductAbstract() === $productAbstractEntity->getIdProductAbstract()) {
                return $productAbstractTransfer;
            }
        }

        return null;
    }

    protected function resetImportStepCaches(): void
    {
        $reflectionClass = new ReflectionClass(ProductCsvImportUrlStep::class);

        foreach ([static::CACHE_PROPERTY_LOCALE, static::CACHE_PROPERTY_PRODUCT_ABSTRACT_ID] as $propertyName) {
            $reflectionClass->getProperty($propertyName)->setValue(null, []);
        }
    }

    protected function createProductFacadeMock(): ProductFacadeInterface
    {
        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock
            ->method('updateProductsUrl')
            ->willReturnCallback(function (array $productAbstractTransfers): array {
                $this->generatedForProductAbstractTransfers = $productAbstractTransfers;

                /** @var \Spryker\Zed\Product\Business\ProductFacadeInterface $productFacade */
                $productFacade = $this->tester->getFacade(static::MODULE_NAME_PRODUCT);

                return $productFacade->updateProductsUrl($productAbstractTransfers);
            });

        return $productFacadeMock;
    }

    protected function createProductAbstractEntity(string $sku, string $name = self::PRODUCT_ABSTRACT_NAME): SpyProductAbstract
    {
        $productAbstractEntity = (new SpyProductAbstract())
            ->setSku($sku)
            ->setAttributes('{}');
        $productAbstractEntity->save();

        (new SpyProductAbstractLocalizedAttributes())
            ->setFkProductAbstract($productAbstractEntity->getIdProductAbstract())
            ->setFkLocale($this->getLocaleEntity(static::LOCALE_NAME_DE)->getIdLocale())
            ->setName($name)
            ->setAttributes('{}')
            ->save();

        return $productAbstractEntity;
    }

    protected function createUrlEntity(SpyProductAbstract $productAbstractEntity, string $localeName, string $url): SpyUrl
    {
        $urlEntity = (new SpyUrl())
            ->setFkResourceProductAbstract($productAbstractEntity->getIdProductAbstract())
            ->setFkLocale($this->getLocaleEntity($localeName)->getIdLocale())
            ->setUrl($url);
        $urlEntity->save();

        return $urlEntity;
    }

    protected function findStoredUrl(SpyProductAbstract $productAbstractEntity, string $localeName): ?string
    {
        return $this->findStoredUrlEntity($productAbstractEntity, $localeName)?->getUrl();
    }

    protected function findStoredUrlEntity(SpyProductAbstract $productAbstractEntity, string $localeName): ?SpyUrl
    {
        return SpyUrlQuery::create()
            ->filterByFkResourceProductAbstract($productAbstractEntity->getIdProductAbstract())
            ->filterByFkLocale($this->getLocaleEntity($localeName)->getIdLocale())
            ->findOne();
    }

    protected function getLocaleEntity(string $localeName): SpyLocale
    {
        $localeEntity = SpyLocaleQuery::create()->filterByLocaleName($localeName)->findOne();

        if ($localeEntity === null) {
            $localeEntity = (new SpyLocale())->setLocaleName($localeName)->setIsActive(true);
            $localeEntity->save();
        }

        return $localeEntity;
    }
}
