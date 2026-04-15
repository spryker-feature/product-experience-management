<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Controller;

use Generated\Shared\Transfer\ImportJobCollectionRequestTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Communication\ProductExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacade getFacade()
 */
class JobController extends AbstractController
{
    protected const string ROUTE_JOB_INDEX = '/product-experience-management/job/index';

    /**
     * @return array<string, mixed>
     */
    public function indexAction(): array
    {
        $table = $this->getFactory()->createImportJobTable();

        return $this->viewResponse(['table' => $table->render()]);
    }

    public function tableAction(): JsonResponse
    {
        $table = $this->getFactory()->createImportJobTable();

        return $this->jsonResponse($table->fetchData());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response|array<string, mixed>
     */
    public function createAction(Request $request): array|Response
    {
        $form = $this->getFactory()->createImportJobForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $importJobTransfer = $form->getData();

            $collectionRequest = (new ImportJobCollectionRequestTransfer())
                ->addImportJob($importJobTransfer)
                ->setIsTransactional(true);

            $collectionResponse = $this->getFacade()->createImportJobCollection($collectionRequest);

            if ($collectionResponse->getErrors()->count() > 0) {
                foreach ($collectionResponse->getErrors() as $errorTransfer) {
                    $this->addErrorMessage($errorTransfer->getMessageOrFail());
                }

                return $this->viewResponse([
                    'form' => $form->createView(),
                ]);
            }

            $this->addSuccessMessage('Import job "%s" created successfully.', ['%s' => $importJobTransfer->getName()]);

            return $this->redirectResponse(static::ROUTE_JOB_INDEX);
        }

        return $this->viewResponse([
            'form' => $form->createView(),
        ]);
    }
}
