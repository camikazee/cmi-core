<?php

declare(strict_types=1);

namespace Core\Dictionary;

final class DictionarySeederRunner
{
    /** @param iterable<DictionarySeederInterface> $seeders */
    public function __construct(
        private readonly iterable $seeders,
        private readonly DictionaryPersisterInterface $persister,
    ) {
    }

    /**
     * Runs all registered seeders (or a specific group).
     *
     * @param string|null $group   Only seed this group; null = all
     * @param bool        $replace Delete existing entries before seeding
     * @return array<string, int>  group => number of entries upserted
     */
    public function run(?string $group = null, bool $replace = false): array
    {
        $results = [];

        foreach ($this->seeders as $seeder) {
            if ($group !== null && $seeder->getGroup() !== $group) {
                continue;
            }

            if ($replace) {
                $this->persister->deleteGroup($seeder->getGroup());
            }

            foreach ($seeder->getEntries() as $entry) {
                $this->persister->upsert(
                    group: $seeder->getGroup(),
                    value: $entry['value'],
                    labelPl: $entry['labelPl'] ?? null,
                    labelEn: $entry['labelEn'] ?? null,
                    sortOrder: $entry['sortOrder'] ?? 0,
                    meta: $entry['meta'] ?? [],
                );
            }

            $this->persister->flush();
            $results[$seeder->getGroup()] = count($seeder->getEntries());
        }

        return $results;
    }

    /** @return string[] */
    public function getRegisteredGroups(): array
    {
        $groups = [];
        foreach ($this->seeders as $seeder) {
            $groups[] = $seeder->getGroup();
        }
        return array_unique($groups);
    }
}
