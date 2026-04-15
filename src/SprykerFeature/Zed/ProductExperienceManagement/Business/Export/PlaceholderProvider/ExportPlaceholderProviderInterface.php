<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Export\PlaceholderProvider;

interface ExportPlaceholderProviderInterface
{
    /**
     * Returns placeholder values from the system configuration.
     * Keys are placeholder names (locale, store, currency, etc.), values are arrays of possible values.
     *
     * @return array<string, array<string>>
     */
    public function getPlaceholderValues(): array;
}
