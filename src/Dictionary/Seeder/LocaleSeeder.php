<?php

declare(strict_types=1);

namespace Core\Dictionary\Seeder;

use Core\Dictionary\DictionarySeederInterface;

final class LocaleSeeder implements DictionarySeederInterface
{
    public function getGroup(): string
    {
        return 'locale';
    }

    public function getEntries(): array
    {
        return [
            ['value' => 'pl', 'labelPl' => 'Polski',  'labelEn' => 'Polish',  'sortOrder' => 1],
            ['value' => 'en', 'labelPl' => 'Angielski', 'labelEn' => 'English', 'sortOrder' => 2],
        ];
    }
}
