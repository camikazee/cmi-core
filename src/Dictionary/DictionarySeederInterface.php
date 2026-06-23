<?php

declare(strict_types=1);

namespace Core\Dictionary;

interface DictionarySeederInterface
{
    public function getGroup(): string;

    /**
     * @return array<int, array{
     *     value: string,
     *     labelPl: string|null,
     *     labelEn: string|null,
     *     sortOrder: int,
     *     meta?: array<string, mixed>
     * }>
     */
    public function getEntries(): array;
}
