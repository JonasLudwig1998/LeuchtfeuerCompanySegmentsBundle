<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Company;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanyEventLog;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanyEventLogRepository;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanySegment;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends FormModel<CompanyEventLog>
 */
class CompanyEventLogModel extends FormModel
{
    public function __construct(
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $logger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $logger, $coreParametersHelper);
    }

    public function getRepository(): CompanyEventLogRepository
    {
        $repository = $this->em->getRepository(CompanyEventLog::class);
        \assert($repository instanceof CompanyEventLogRepository);

        return $repository;
    }

    public function saveCompanyEventLog(string $action, Company $company, CompanySegment $companySegment): void
    {
        $companyEventLog = new CompanyEventLog();
        $companyEventLog->setCompany($company);
        $companyEventLog->setBundle('company');
        $companyEventLog->setAction($action);
        $companyEventLog->setObject('company_segment');
        $companyEventLog->setObjectId($companySegment->getId());
        $companyEventLog->setDateAdded(new \DateTime());
        $userId      = null; // Set the user ID if available
        $userName    = 'System'; // or use the actual user name if available
        $currentUser = $this->userHelper->getUser();
        if (!is_null($currentUser)) {
            $userId   = $currentUser->getId();
            $userName = $currentUser->getUsername();
        }
        $companySegmentId   = is_null($companySegment->getId()) ? 0 : $companySegment->getId();
        $companySegmentName = is_null($companySegment->getName()) ? '' : $companySegment->getName();
        $companyEventLog->setProperties([
            'company_segment_id'   => $companySegmentId,
            'company_segment_name' => $companySegmentName,
            'company_id'           => $company->getId(),
            'object_description'   => $companySegmentName,
        ]);
        $companyEventLog->setUserId($userId); // Set the user ID if available
        $companyEventLog->setUserName($userName); // or use the actual user name if available
        $this->saveEntity($companyEventLog);
    }
}
