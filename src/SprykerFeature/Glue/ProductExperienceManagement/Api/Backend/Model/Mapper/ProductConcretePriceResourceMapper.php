<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;

class ProductConcretePriceResourceMapper implements ProductConcretePriceResourceMapperInterface
{
    protected const string KEY_PRICE_TYPE_NAME = 'priceTypeName';

    protected const string KEY_CURRENCY_CODE = 'currencyCode';

    protected const string KEY_STORE_NAME = 'storeName';

    protected const string KEY_GROSS_AMOUNT = 'grossAmount';

    protected const string KEY_NET_AMOUNT = 'netAmount';

    protected const string KEY_VOLUME_PRICES = 'volumePrices';

    protected const string KEY_VOLUME_PRICES_STORAGE = 'volume_prices';

    protected const string KEY_VOLUME_NET_PRICE = 'net_price';

    protected const string KEY_VOLUME_GROSS_PRICE = 'gross_price';

    protected const string KEY_NET_PRICE = 'netPrice';

    protected const string KEY_GROSS_PRICE = 'grossPrice';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapPrices(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $priceCollection = [];

        foreach ($productConcreteTransfer->getPrices() as $priceProductTransfer) {
            $uuid = $priceProductTransfer->getMoneyValue()?->getUuid();

            if ($uuid !== null && isset($priceCollection[$uuid])) {
                continue;
            }

            $flattenedPrice = $this->flattenPrice($priceProductTransfer);
            $key = $uuid ?? count($priceCollection);
            $priceCollection[$key] = $flattenedPrice;
        }

        return array_values($priceCollection);
    }

    /**
     * @return array<string, mixed>
     */
    protected function flattenPrice(PriceProductTransfer $priceProductTransfer): array
    {
        $moneyValueTransfer = $priceProductTransfer->getMoneyValue();

        return [
            ProductConcreteTransferToResourceMapper::KEY_UUID => $moneyValueTransfer?->getUuid(),
            static::KEY_PRICE_TYPE_NAME => $priceProductTransfer->getPriceTypeName(),
            static::KEY_CURRENCY_CODE => $moneyValueTransfer?->getCurrency()?->getCode(),
            static::KEY_STORE_NAME => $moneyValueTransfer?->getStore()?->getName(),
            static::KEY_GROSS_AMOUNT => $moneyValueTransfer?->getGrossAmount(),
            static::KEY_NET_AMOUNT => $moneyValueTransfer?->getNetAmount(),
            static::KEY_VOLUME_PRICES => $this->decodeVolumePrices($moneyValueTransfer?->getPriceData()),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function decodeVolumePrices(?string $priceData): array
    {
        if ($priceData === null || $priceData === '') {
            return [];
        }

        $decoded = json_decode($priceData, true);

        if (!is_array($decoded)) {
            return [];
        }

        $storageVolumePrices = $decoded[static::KEY_VOLUME_PRICES_STORAGE] ?? [];
        $volumePrices = [];

        foreach ($storageVolumePrices as $storageVolumePrice) {
            $volumePrices[] = [
                ProductConcreteTransferToResourceMapper::KEY_QUANTITY => $storageVolumePrice[ProductConcreteTransferToResourceMapper::KEY_QUANTITY] ?? null,
                static::KEY_NET_PRICE => $storageVolumePrice[static::KEY_VOLUME_NET_PRICE] ?? null,
                static::KEY_GROSS_PRICE => $storageVolumePrice[static::KEY_VOLUME_GROSS_PRICE] ?? null,
            ];
        }

        return $volumePrices;
    }
}
