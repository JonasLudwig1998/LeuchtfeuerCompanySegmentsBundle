<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\EventListener;

use Mautic\CoreBundle\Helper\UserHelper;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Event\CompanySegmentAddEvent;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Event\CompanySegmentRemoveEvent;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Integration\Config;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Model\CompanyEventLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddRemoveCompanyEventLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CompanyEventLogModel $companyEventLogModel,
    ) {
        // Constructor logic if needed
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CompanySegmentAddEvent::class  => [
                ['onAddCompanySegmentEvent', 0],
            ],
            CompanySegmentRemoveEvent::class  => [
                ['onRemoveCompanySegmentEvent', 0],
            ],
        ];
    }

    public function onAddCompanySegmentEvent(CompanySegmentAddEvent $companySegmentAddEvent): void
    {
        $this->companyEventLogModel->saveCompanyEventLog(
            'added',
            $companySegmentAddEvent->getCompany(),
            $companySegmentAddEvent->getCompanySegment()
        );
    }

    public function onRemoveCompanySegmentEvent(CompanySegmentRemoveEvent $companySegmentAddEvent): void
    {
        $this->companyEventLogModel->saveCompanyEventLog(
            'removed',
            $companySegmentAddEvent->getCompany(),
            $companySegmentAddEvent->getCompanySegment()
        );
    }
}
