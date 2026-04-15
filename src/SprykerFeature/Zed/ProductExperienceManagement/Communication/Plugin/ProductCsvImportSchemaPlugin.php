<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportDataProviderInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportSchemaPluginInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportSchemaPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory getBusinessFactory()
 */
class ProductCsvImportSchemaPlugin extends AbstractPlugin implements ImportSchemaPluginInterface, ExportSchemaPluginInterface
{
    protected const string TYPE = 'products-csv-import';

    public function getType(): string
    {
        return static::TYPE;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface>
     */
    public function getImportSteps(): array
    {
        return $this->getBusinessFactory()->createProductCsvImportSteps();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportStepInterface>
     */
    public function getExportSteps(): array
    {
        return $this->getBusinessFactory()->createProductCsvExportSteps();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<int, array<string, string>>
     */
    public function getSchema(): array
    {
        return [
            ['property_name_in_file' => 'Abstract SKU', 'system_property_name' => 'abstract_sku'],
            ['property_name_in_file' => 'Concrete SKU', 'system_property_name' => 'concrete_sku'],
            ['property_name_in_file' => 'Product Status', 'system_property_name' => 'product_status'],
            ['property_name_in_file' => 'Merchant', 'system_property_name' => 'merchant'],
            ['property_name_in_file' => 'Stores', 'system_property_name' => 'stores'],
            ['property_name_in_file' => 'Name ({locale})', 'system_property_name' => 'name.{locale}'],
            ['property_name_in_file' => 'Description ({locale})', 'system_property_name' => 'description.{locale}'],
            ['property_name_in_file' => 'Categories', 'system_property_name' => 'categories'],
            ['property_name_in_file' => 'Tax Set Name', 'system_property_name' => 'tax_set_name'],
            ['property_name_in_file' => 'URL ({locale})', 'system_property_name' => 'url.{locale}'],
            ['property_name_in_file' => 'Attributes ({locale})', 'system_property_name' => 'attributes.{locale}'],
            ['property_name_in_file' => 'Stock ({warehouse})', 'system_property_name' => 'stock.{warehouse}'],
            ['property_name_in_file' => 'Shipment Types', 'system_property_name' => 'shipment_types'],
            ['property_name_in_file' => 'Price ({price_mode}-{store}-{currency}-{price_dimension})', 'system_property_name' => 'price.{price_mode}.{store}.{currency}.{price_dimension}'],
            ['property_name_in_file' => 'Image {size} ({locale}-{sort_order})', 'system_property_name' => 'images.{size}.{locale}.{sort_order}'],
        ];
    }

    public function getExportDataProvider(): ExportDataProviderInterface
    {
        return $this->getBusinessFactory()->createProductCsvExportDataProvider();
    }
}
