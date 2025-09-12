<?php

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Model;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

class CompanyFieldModelDecorated extends FieldModel
{
    /**
     * @param string $object
     *
     * @return array<array<string, mixed>>
     */
    public function getPublishedFieldArrays($object = 'lead'): array
    {
        $entities = $this->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                        ],
                        [
                            'column' => 'f.object',
                            'expr'   => 'eq',
                            'value'  => $object,
                        ],
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
                'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
            ]
        );

        $rows = [];
        if ($entities instanceof Paginator) {
            $rows = iterator_to_array($entities->getIterator(), true); // preserve keys
        } elseif (is_array($entities)) {
            $rows = $entities;
        }

        foreach ($rows as $k => $row) {
            if (
                is_array($row)
                && array_key_exists('properties', $row)
                && array_key_exists('list', $row['properties'])
                && is_array($row['properties']['list'])
                && [] !== $row['properties']['list']
            ) {
                uasort($row['properties']['list'], fn ($a, $b): int => strcasecmp($a['value'] ?? '', $b['value'] ?? ''));
                $row['properties']['list'] = array_values($row['properties']['list']);
                $rows[$k]                  = $row;
            }
        }

        return $rows;
    }
}
