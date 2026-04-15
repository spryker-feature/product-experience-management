<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportJob\Writer;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\ImportJobCollectionRequestTransfer;
use Generated\Shared\Transfer\ImportJobCollectionResponseTransfer;
use SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementEntityManagerInterface;

class ImportJobWriter implements ImportJobWriterInterface
{
    /**
     * @param array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportSchemaPluginInterface> $schemaPlugins
     */
    public function __construct(
        protected ProductExperienceManagementEntityManagerInterface $entityManager,
        protected array $schemaPlugins,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createImportJobCollection(ImportJobCollectionRequestTransfer $collectionRequestTransfer): ImportJobCollectionResponseTransfer
    {
        $response = new ImportJobCollectionResponseTransfer();

        foreach ($collectionRequestTransfer->getImportJobs() as $importJobTransfer) {
            if (!$importJobTransfer->getReference()) {
                $importJobTransfer->setReference($this->generateReference($importJobTransfer->getNameOrFail()));
            }

            if (!$importJobTransfer->getDefinition()) {
                $definition = $this->resolveDefinitionByType($importJobTransfer->getTypeOrFail());

                if ($definition === []) {
                    $response->addError(
                        (new ErrorTransfer())->setMessage(
                            sprintf('No schema found for job type "%s".', $importJobTransfer->getType()),
                        ),
                    );

                    continue;
                }

                $importJobTransfer->setDefinition($definition);
            }

            $persistedJob = $this->entityManager->createImportJob($importJobTransfer);
            $response->addImportJob($persistedJob);
        }

        return $response;
    }

    protected function generateReference(string $name): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = (string)preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolveDefinitionByType(string $type): array
    {
        foreach ($this->schemaPlugins as $schemaPlugin) {
            if ($schemaPlugin->getType() === $type) {
                return $schemaPlugin->getSchema();
            }
        }

        return [];
    }
}
