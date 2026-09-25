<?php

declare(strict_types=1);

namespace Core\Audit\Attribute;

/**
 * Opts a Doctrine entity into the entity-change audit trail.
 *
 * Only entities carrying this attribute are recorded. Field values listed in $redactedFields are replaced
 * with "[redacted]" (the fact that they changed is still recorded); $ignoredFields are left out entirely.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Auditable
{
    /**
     * @param list<string> $redactedFields
     * @param list<string> $ignoredFields
     */
    public function __construct(
        public string $resourceType,
        public array $redactedFields = [],
        public array $ignoredFields = [],
    ) {
    }
}
