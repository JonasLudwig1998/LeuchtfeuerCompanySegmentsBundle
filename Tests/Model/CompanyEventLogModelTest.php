<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanyEventLog;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanyEventLogRepository;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Model\CompanyEventLogModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CompanyEventLogModelTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Doctrine\ORM\EntityManagerInterface */
    private $em;
    private CorePermissions $security;
    private EventDispatcherInterface $dispatcher;
    private UrlGeneratorInterface $router;
    private Translator $translator;
    private UserHelper $userHelper;
    private LoggerInterface $logger;
    private CoreParametersHelper $coreParametersHelper;

    protected function setUp(): void
    {
        $this->em                   = $this->createMock(EntityManagerInterface::class);
        $this->security             = $this->createMock(CorePermissions::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->router               = $this->createMock(UrlGeneratorInterface::class);
        $this->translator           = $this->createMock(Translator::class);
        $this->userHelper           = $this->createMock(UserHelper::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    public function testGetRepositoryReturnsCompanyEventLogRepository(): void
    {
        $repo = $this->createMock(CompanyEventLogRepository::class);
        $this->em->method('getRepository')->with(CompanyEventLog::class)->willReturn($repo);

        $model = new CompanyEventLogModel(
            $this->em,
            $this->security,
            $this->dispatcher,
            $this->router,
            $this->translator,
            $this->userHelper,
            $this->logger,
            $this->coreParametersHelper
        );

        self::assertSame($repo, $model->getRepository());
    }
}
