<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Segment\Query\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompanyLeadSegmentMembershipFilterQueryBuilder extends BaseFilterQueryBuilder implements EventSubscriberInterface
{
    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);
    }

    public static function getServiceId(): string
    {
        return self::class;
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        if ('contactsegmentmembership' !== $filter->getField()) {
            throw new \RuntimeException('This filter only supports any_company_contact field');
        }

        $companiesTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'companies');
        $segmentIds = $filter->getParameterValue();


        if (OperatorOptions::EMPTY === $filter->getOperator() || 'notEmpty' === $filter->getOperator()) {
            $sub = $queryBuilder->createQueryBuilder();
            $cl = $this->generateRandomParameterName();
            $lll = $this->generateRandomParameterName();
            $isPrimaryParam = $this->generateRandomParameterName();

            $sub->select('1')
                ->from(MAUTIC_TABLE_PREFIX.'companies_leads', $cl)
                ->join($cl, MAUTIC_TABLE_PREFIX.'lead_lists_leads', $lll, $lll.'.lead_id = '.$cl.'.lead_id')
                ->where($sub->expr()->eq($cl.'.company_id', $companiesTableAlias.'.id'))
                ->andWhere($sub->expr()->eq($cl.'.is_primary', ':'.$isPrimaryParam));

            $queryBuilder->setParameter($isPrimaryParam, 1);

            $expr = (OperatorOptions::EMPTY === $filter->getOperator())
                ? $queryBuilder->expr()->notExists($sub->getSQL())
                : $queryBuilder->expr()->exists($sub->getSQL());

            $queryBuilder->addLogic($expr, $filter->getGlue());
            return $queryBuilder;
        }

        if (!is_array($segmentIds)) {
            $segmentIds = [(int) $segmentIds];
        }

        $operator = $filter->getOperator();
        $isExclusion = in_array($operator, ['notExists', 'notIn'], true);

        $sub = $queryBuilder->createQueryBuilder();
        $cl = $this->generateRandomParameterName();
        $lll = $this->generateRandomParameterName();
        $segmentIdsParam = $this->generateRandomParameterName();
        $isPrimaryParam = $this->generateRandomParameterName();

        $sub->select('1')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', $cl)
            ->join($cl, MAUTIC_TABLE_PREFIX.'lead_lists_leads', $lll, $lll.'.lead_id = '.$cl.'.lead_id')
            ->where($sub->expr()->eq($cl.'.company_id', $companiesTableAlias.'.id'))
            ->andWhere($sub->expr()->eq($cl.'.is_primary', ':'.$isPrimaryParam))
            ->andWhere($sub->expr()->in($lll.'.leadlist_id', ':'.$segmentIdsParam));

        $queryBuilder->setParameter($segmentIdsParam, $segmentIds, ArrayParameterType::STRING);
        $queryBuilder->setParameter($isPrimaryParam, 1);

        $expr = $isExclusion
            ? $queryBuilder->expr()->notExists($sub->getSQL())
            : $queryBuilder->expr()->exists($sub->getSQL());

        $queryBuilder->addLogic($expr, $filter->getGlue());

        return $queryBuilder;
    }

    public function onAddFilter(SegmentDictionaryGenerationEvent $event): void
    {
        $event->addTranslation('contactsegmentmembership', [
            'type' => self::getServiceId(),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onAddFilter',
        ];
    }
}