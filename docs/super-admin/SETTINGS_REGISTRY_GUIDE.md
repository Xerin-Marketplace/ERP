# SETTINGS REGISTRY — MODULE SELF-REGISTRATION

In a module's `*ServiceProvider::boot()`:

```php
use Modules\SystemAdministration\Services\SettingRegistry;

SettingRegistry::register('procurement', [
    'procurement.rfq_required' => [
        'group' => 'Procurement', 'label' => 'Require RFQ',
        'type' => 'bool', 'default' => true,
        'rules' => 'boolean', 'risk' => 'medium',
    ],
]);
```

- Keys MUST be `{module}.`-prefixed (enforced — foreign namespaces throw).
- Required def keys: group, label, type, default, rules, risk.
- Read at runtime: `app(\App\Services\SettingsService::class)->get('procurement.rfq_required')`.
- Admin edits flow through SettingCatalog → validation → version → audit.
- Tests: `Modules/SystemAdministration/tests/Feature/SettingRegistryTest.php`.
