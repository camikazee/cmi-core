<?php

declare(strict_types=1);

namespace Core\Dictionary\Seeder;

use Core\Dictionary\DictionarySeederInterface;

final class AccountStatusSeeder implements DictionarySeederInterface
{
    public function getGroup(): string
    {
        return 'account_status';
    }

    public function getEntries(): array
    {
        return [
            ['value' => 'active',    'labelPl' => 'Aktywne',      'labelEn' => 'Active',    'sortOrder' => 1, 'meta' => ['color' => '#10b981']],
            ['value' => 'blocked',   'labelPl' => 'Zablokowane',  'labelEn' => 'Blocked',   'sortOrder' => 2, 'meta' => ['color' => '#ef4444']],
            ['value' => 'suspended', 'labelPl' => 'Zawieszone',   'labelEn' => 'Suspended', 'sortOrder' => 3, 'meta' => ['color' => '#f59e0b']],
            ['value' => 'deleted',   'labelPl' => 'Usunięte',     'labelEn' => 'Deleted',   'sortOrder' => 4, 'meta' => ['color' => '#6b7280']],
        ];
    }
}
