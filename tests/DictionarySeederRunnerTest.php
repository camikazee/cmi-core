<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Dictionary\DictionaryPersisterInterface;
use Core\Dictionary\DictionarySeederInterface;
use Core\Dictionary\DictionarySeederRunner;
use PHPUnit\Framework\TestCase;

final class DictionarySeederRunnerTest extends TestCase
{
    public function testItRunsSeedersAndPersistsEntries(): void
    {
        $persister = new InMemoryDictionaryPersister();
        $runner = new DictionarySeederRunner([new TestDictionarySeeder()], $persister);

        $result = $runner->run();

        self::assertSame(['asset_status' => 2], $result);
        self::assertCount(2, $persister->entries);
        self::assertTrue($persister->flushed);
    }
}

final class TestDictionarySeeder implements DictionarySeederInterface
{
    public function getGroup(): string { return 'asset_status'; }
    public function getEntries(): array
    {
        return [
            ['value' => 'available', 'labelEn' => 'Available'],
            ['value' => 'missing', 'labelEn' => 'Missing'],
        ];
    }
}

final class InMemoryDictionaryPersister implements DictionaryPersisterInterface
{
    /** @var list<array<string,mixed>> */
    public array $entries = [];
    public bool $flushed = false;
    /** @var list<string> */
    public array $deletedGroups = [];

    public function upsert(string $group, string $value, ?string $labelPl = null, ?string $labelEn = null, int $sortOrder = 0, array $meta = []): void
    {
        $this->entries[] = compact('group', 'value', 'labelPl', 'labelEn', 'sortOrder', 'meta');
    }

    public function deleteGroup(string $group): void { $this->deletedGroups[] = $group; }
    public function flush(): void { $this->flushed = true; }
}
