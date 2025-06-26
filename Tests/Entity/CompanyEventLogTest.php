<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\Company;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanyEventLog;
use PHPUnit\Framework\TestCase;

class CompanyEventLogTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $eventLog = new CompanyEventLog();

        self::assertInstanceOf(\DateTime::class, $eventLog->getDateAdded());
        self::assertNull($eventLog->getCompany());
        self::assertNull($eventLog->getUserId());
        self::assertNull($eventLog->getUserName());
        self::assertNull($eventLog->getBundle());
        self::assertNull($eventLog->getObject());
        self::assertNull($eventLog->getObjectId());
        self::assertNull($eventLog->getAction());
        self::assertNull($eventLog->getProperties());
    }

    public function testSettersAndGetters(): void
    {
        $eventLog = new CompanyEventLog();
        $company  = $this->createMock(Company::class);

        $eventLog->setCompany($company);
        $eventLog->setUserId(42);
        $eventLog->setUserName('John Doe');
        $eventLog->setBundle('TestBundle');
        $eventLog->setObject('TestObject');
        $eventLog->setObjectId(123);
        $eventLog->setAction('create');
        $date = new \DateTime('2020-01-01');
        $eventLog->setDateAdded($date);
        $eventLog->setProperties(['foo' => 'bar']);

        self::assertSame($company, $eventLog->getCompany());
        self::assertSame(42, $eventLog->getUserId());
        self::assertSame('John Doe', $eventLog->getUserName());
        self::assertSame('TestBundle', $eventLog->getBundle());
        self::assertSame('TestObject', $eventLog->getObject());
        self::assertSame(123, $eventLog->getObjectId());
        self::assertSame('create', $eventLog->getAction());
        self::assertSame($date, $eventLog->getDateAdded());
        self::assertSame(['foo' => 'bar'], $eventLog->getProperties());
    }

    public function testAddProperty(): void
    {
        $eventLog = new CompanyEventLog();
        $eventLog->setProperties(['foo' => 'bar']);
        $eventLog->addProperty('baz', 'qux');

        self::assertSame(['foo' => 'bar', 'baz' => 'qux'], $eventLog->getProperties());
    }
}
