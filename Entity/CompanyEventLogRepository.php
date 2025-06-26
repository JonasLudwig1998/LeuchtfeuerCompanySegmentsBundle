<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Company;

class CompanyEventLogRepository extends CommonRepository
{
    /**
     * Returns array with failed rows.
     *
     * @param string              $importId
     * @param string              $bundle
     * @param string              $object
     * @param array<string,mixed> $args
     *
     * @return array<mixed>
     */
    public function getFailedRows($importId, array $args = [], $bundle = 'company', $object = 'import'): array
    {
        return $this->getSpecificRows($importId, 'failed', $args, $bundle, $object);
    }

    /**
     * @param array<string,mixed> $args
     *
     * @return array<mixed>
     */
    public function getEntities(array $args = []): array
    {
        $entities = parent::getEntities($args);

        if ($entities instanceof \Traversable) {
            $entities = iterator_to_array($entities);
        }

        foreach ($entities as $key => $row) {
            if (
                isset($row['properties'])
                && is_array($row['properties'])
                && isset($row['properties']['error'])
                && preg_match('/SQLSTATE\[\w+\]: (.*)/', $row['properties']['error'], $matches)
            ) {
                if (isset($entities[$key]['properties']['error'])) {
                    $entities[$key]['properties']['error'] = $matches[1];
                } elseif (is_object($entities[$key]) && isset($entities[$key]->properties->error)) {
                    $entities[$key]->properties->error = $matches[1];
                }
            }
        }

        return $entities;
    }

    /**
     * Returns paginator with specific type of rows.
     *
     * @param string|int          $objectId
     * @param string              $bundle
     * @param string              $object
     * @param string|int          $action
     * @param array<string,mixed> $args
     *
     * @return array<mixed>
     */
    public function getSpecificRows($objectId, $action, array $args = [], $bundle = 'lead', $object = 'import'): array
    {
        return $this->getEntities(
            array_merge(
                [
                    'start'          => 0,
                    'limit'          => 100,
                    'orderBy'        => $this->getTableAlias().'.dateAdded',
                    'orderByDir'     => 'ASC',
                    'filter'         => [
                        'force' => [
                            [
                                'column' => $this->getTableAlias().'.bundle',
                                'expr'   => 'eq',
                                'value'  => $bundle,
                            ],
                            [
                                'column' => $this->getTableAlias().'.object',
                                'expr'   => 'eq',
                                'value'  => $object,
                            ],
                            [
                                'column' => $this->getTableAlias().'.action',
                                'expr'   => 'eq',
                                'value'  => $action,
                            ],
                            [
                                'column' => $this->getTableAlias().'.objectId',
                                'expr'   => 'eq',
                                'value'  => $objectId,
                            ],
                        ],
                    ],
                    'hydration_mode' => 'HYDRATE_ARRAY',
                ],
                $args
            )
        );
    }

    /**
     * Updates lead ID (e.g. after a company merge).
     *
     * @param int $fromCompanyId
     * @param int $toCompanyId
     */
    public function updateCompany($fromCompanyId, $toCompanyId): void
    {
        $toCompanyId = (int) $toCompanyId;
        $toCompanyId = (string) $toCompanyId;
        $q           = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'company_event_log')
            ->set('company_id', $toCompanyId)
            ->where('company_id = '.(int) $fromCompanyId)
            ->executeStatement();
    }

    /**
     * Defines default table alias for company_event_log table.
     */
    public function getTableAlias(): string
    {
        return 'cel';
    }
}
