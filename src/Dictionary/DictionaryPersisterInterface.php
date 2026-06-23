<?php

declare(strict_types=1);

namespace Core\Dictionary;

interface DictionaryPersisterInterface
{
    public function upsert(
        string $group,
        string $value,
        ?string $labelPl,
        ?string $labelEn,
        int $sortOrder,
        array $meta = [],
    ): void;

    public function flush(): void;

    public function deleteGroup(string $group): void;
}
