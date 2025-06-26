<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Entity\CommonRepository;
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

    public function getRepository(): CompanyEventLogRepository|EntityRepository|CommonRepository
    {
        return $this->em->getRepository(CompanyEventLog::class);
    }

    public function saveCompanyEventLog(string $action, Company $company, CompanySegment $companySegment): void
    {
        $companyEventLog = new CompanyEventLog();
        $companyEventLog->setCompany($company);
        $companyEventLog->setBundle('company');
        $companyEventLog->setAction($action);
        $companyEventLog->setObject('segment');
        $companyEventLog->setObjectId($companySegment->getId());
        $companyEventLog->setDateAdded(new \DateTime());
        $userId      = null; // Set the user ID if available
        $userName    = 'System'; // or use the actual user name if available
        $currentUser = $this->userHelper->getUser();
        if ($currentUser) {
            $userId   = $currentUser->getId();
            $userName = $currentUser->getUsername();
        }
        $companyEventLog->setProperties([
            'company_segment_id'   => $companySegment->getId(),
            'company_segment_name' => $companySegment->getName(),
            'company_id'           => $company->getId(),
            'object_description'   => $companySegment->getName(),
        ]);
        $companyEventLog->setUserId($userId); // Set the user ID if available
        $companyEventLog->setUserName($userName); // or use the actual user name if available
        $this->saveEntity($companyEventLog);
    }
}
