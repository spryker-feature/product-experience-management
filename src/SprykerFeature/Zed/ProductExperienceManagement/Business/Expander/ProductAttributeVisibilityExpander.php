<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Expander;

class ProductAttributeVisibilityExpander implements ProductAttributeVisibilityExpanderInterface
{
    protected const string COL_VISIBILITY = 'visibility';

    protected const string BADGE_TEMPLATE = '<span class="badge text-bg-light">%s</span>';

    protected const string KEY_EXTENSION_COLUMN_VALUES = 'extension_column_values';

    /**
     * @param array<array<string, mixed>> $suggestKeys
     *
     * @return array<array<string, mixed>>
     */
    public function expandSuggestKeysWithVisibility(array $suggestKeys): array
    {
        foreach ($suggestKeys as &$suggestKey) {
            $visibility = (string)($suggestKey[static::COL_VISIBILITY] ?? '');
            $suggestKey[static::KEY_EXTENSION_COLUMN_VALUES][] = $this->formatVisibilityAsHtml($visibility);
        }

        return $suggestKeys;
    }

    protected function formatVisibilityAsHtml(string $visibility): string
    {
        if ($visibility === '') {
            return '';
        }

        $labels = [];

        foreach (explode(',', $visibility) as $type) {
            $labels[] = sprintf(static::BADGE_TEMPLATE, trim($type));
        }

        return implode(' ', $labels);
    }
}
