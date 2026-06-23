# Dictionary

Dictionaries are reusable option lists seeded from code. Core provides only contracts and a runner; your application decides where entries are stored.

```php
final class AssetStatusSeeder implements DictionarySeederInterface
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
```

Run seeders:

```bash
php bin/console core:dictionary:seed
php bin/console core:dictionary:seed --group=asset_status --replace
```
