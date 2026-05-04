<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Expander;

interface ProductAttributeVisibilityExpanderInterface
{
    /**
     * @param array<array<string, mixed>> $suggestKeys
     *
     * @return array<array<string, mixed>>
     */
    public function expandSuggestKeysWithVisibility(array $suggestKeys): array;
}
