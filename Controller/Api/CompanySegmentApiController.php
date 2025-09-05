<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanySegment;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Model\CompanySegmentModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<CompanySegment>
 */
class CompanySegmentApiController extends CommonApiController
{
    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        MauticFactory $factory,
        private CompanySegmentModel $companySegmentModel,
    ) {
        $this->model             = $this->companySegmentModel;
        $this->entityClass       = CompanySegment::class;
        $this->entityNameOne     = 'companysegment';
        $this->entityNameMulti   = 'companysegments';
        $this->permissionBase    = 'companysegment:companysegments';
        parent::__construct(
            $security,
            $translator,
            $entityResultHelper,
            $router,
            $formFactory,
            $appVersion,
            $requestStack,
            $doctrine,
            $modelFactory,
            $dispatcher,
            $coreParametersHelper,
            $factory
        );
    }

    /**
     * Deletes an entity.
     *
     * @param int $id Entity ID
     *
     * @return Response
     */
    public function deleteEntityAction($id)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->checkEntityAccess($entity, 'delete')) {
                return $this->accessDenied();
            }
            $model = $this->getModel(CompanySegmentModel::class);
            \assert($model instanceof CompanySegmentModel);
            $dependents = $model->getSegmentsWithDependenciesOnSegment($id, 'id');
            if (!empty($dependents)) {
                $errorTranslator = $this->translator->trans('mautic.company_segments.api.error.delete_has_dependencies', ['%segments%' => implode(', ', $dependents)]);

                return $this->returnError($errorTranslator);
            }
            parent::deleteEntityAction($id);
        }

        return $this->notFound();
    }

    /**
     * Delete a batch of entities.
     *
     * @return array|Response
     */
    public function deleteEntitiesAction(Request $request)
    {
        $parameters = $request->query->all();

        $valid = $this->validateBatchPayload($parameters);
        if ($valid instanceof Response) {
            return $valid;
        }

        $errors            = [];
        $entities          = $this->getBatchEntities($parameters, $errors, true);
        $ids               =  [];
        foreach ($entities as $entity) {
            assert($entity instanceof CompanySegment || null === $entity);
            if (null !== $entity && null !== $entity->getId()) {
                $ids[] = $entity->getId();
            }
        }
        if (!empty($ids)) {
            $model = $this->getModel(CompanySegmentModel::class);
            \assert($model instanceof CompanySegmentModel);
            $canNotBeDeleted  = $model->canNotBeDeleted($ids);
            $errorMessage     = $this->translator->trans('mautic.lead.list.error.cannot.delete.batch', ['%segments%' => implode(', ', $canNotBeDeleted)]);
            $result['errors'] = $errorMessage;

            return $this->returnError(json_encode($result), Response::HTTP_PRECONDITION_FAILED);
        }

        $this->inBatchMode = true;

        // Generate the view before deleting so that the IDs are still populated before Doctrine removes them
        $payload = [$this->entityNameMulti => $entities];
        $view    = $this->view($payload, Response::HTTP_OK);
        $this->setSerializationContext($view);
        $response = $this->handleView($view);

        foreach ($entities as $key => $entity) {
            if (null === $entity || !$entity->getId()) {
                $this->setBatchError($key, 'mautic.core.error.notfound', Response::HTTP_NOT_FOUND, $errors, $entities, $entity);
                continue;
            }

            if (!$this->checkEntityAccess($entity, 'delete')) {
                $this->setBatchError($key, 'mautic.core.error.accessdenied', Response::HTTP_FORBIDDEN, $errors, $entities, $entity);
                continue;
            }

            $this->model->deleteEntity($entity);
            $this->doctrine->getManager()->detach($entity);
        }

        if (!empty($errors)) {
            $content           = json_decode($response->getContent(), true);
            $content['errors'] = $errors;
            $response->setContent(json_encode($content));
        }

        return $response;
    }
}
