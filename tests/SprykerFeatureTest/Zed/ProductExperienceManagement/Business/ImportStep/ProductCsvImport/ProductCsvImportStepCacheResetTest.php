<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionProperty;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\AbstractProductCsvImportStep;

/**
 * Guards every product import step against a lookup cache that `resetCache()` forgets: a static property added
 * later, or a line dropped from a reset body, fails here without a hand-written test per step.
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group ProductCsvImportStepCacheResetTest
 */
class ProductCsvImportStepCacheResetTest extends Unit
{
    protected const string STEP_FILE_PATTERN = 'ProductCsvImport*Step.php';

    /**
     * @dataProvider productCsvImportStepDataProvider
     *
     * @param class-string<\SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\AbstractProductCsvImportStep> $stepClass
     */
    public function testResetCacheRestoresEveryStaticPropertyToItsDefault(string $stepClass): void
    {
        // Arrange
        $reflection = new ReflectionClass($stepClass);
        $defaults = $reflection->getDefaultProperties();
        $staticProperties = $reflection->getProperties(ReflectionProperty::IS_STATIC);

        foreach ($staticProperties as $property) {
            $property->setValue(null, $this->buildFilledValue($property, $defaults[$property->getName()]));
        }

        try {
            // Act
            $reflection->newInstanceWithoutConstructor()->resetCache();

            // Assert
            foreach ($staticProperties as $property) {
                $this->assertSame(
                    $defaults[$property->getName()],
                    $property->getValue(),
                    sprintf('%s::$%s is not cleared by resetCache()', $stepClass, $property->getName()),
                );
            }
        } finally {
            foreach ($staticProperties as $property) {
                $property->setValue(null, $defaults[$property->getName()]);
            }
        }
    }

    /**
     * @dataProvider productCsvImportStepDataProvider
     *
     * @param class-string<\SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\AbstractProductCsvImportStep> $stepClass
     */
    public function testAStepThatDeclaresAStaticPropertyDeclaresItsOwnResetCache(string $stepClass): void
    {
        // Arrange
        $reflection = new ReflectionClass($stepClass);
        $ownStaticProperties = array_filter(
            $reflection->getProperties(ReflectionProperty::IS_STATIC),
            static fn (ReflectionProperty $property): bool => $property->getDeclaringClass()->getName() === $stepClass,
        );

        // Act
        $resetCacheDeclaringClass = $reflection->getMethod('resetCache')->getDeclaringClass()->getName();

        // Assert
        $this->assertTrue(
            $ownStaticProperties === [] || $resetCacheDeclaringClass === $stepClass,
            sprintf('%s declares static properties but inherits the no-op resetCache() from %s', $stepClass, $resetCacheDeclaringClass),
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function productCsvImportStepDataProvider(): array
    {
        $stepDirectory = dirname((string)(new ReflectionClass(AbstractProductCsvImportStep::class))->getFileName());
        $stepClasses = [];

        foreach (glob(sprintf('%s/%s', $stepDirectory, static::STEP_FILE_PATTERN)) ?: [] as $stepFile) {
            $shortName = basename($stepFile, '.php');
            $stepClasses[$shortName] = [sprintf('%s\\%s', (new ReflectionClass(AbstractProductCsvImportStep::class))->getNamespaceName(), $shortName)];
        }

        return $stepClasses;
    }

    /**
     * Builds a value that differs from the default and still satisfies the declared type, so a reset body that
     * skips the property is caught instead of a type error.
     */
    protected function buildFilledValue(ReflectionProperty $property, mixed $default): mixed
    {
        $type = (string)$property->getType();

        return match (ltrim($type, '?')) {
            'array' => ['filled' => true],
            'bool' => !$default,
            'int' => (int)$default + 1,
            'string' => $default . 'filled',
            default => $this->fail(sprintf('%s::$%s has the static type "%s" this test cannot fill; extend buildFilledValue()', $property->getDeclaringClass()->getName(), $property->getName(), $type)),
        };
    }
}
