<?php

declare(strict_types=1);

namespace Core\Repository\Filters;

use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Class DeletedFilter.
 */
class DeletedFilter extends SQLFilter
{
    /**
     * @param string $targetTableAlias
     */
    public function addFilterConstraint(ClassMetaData $targetEntity, $targetTableAlias): string
    {
        if (in_array('Core\\Model\\Metadata\\DeleteInterface', class_implements($targetEntity->getName()), true)) {
            return sprintf('%s.deleted = 0', $targetTableAlias);
        }

        return '';
    }
}
