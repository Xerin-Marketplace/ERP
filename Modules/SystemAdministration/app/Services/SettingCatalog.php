<?php

namespace Modules\SystemAdministration\Services;

use App\Models\SystemSetting;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\SystemAdministration\Models\SettingVersion;

/**
 * Typed, schema-validated catalog of every configurable system setting/rule.
 *
 * Definitions live in code (safe, reviewable); values persist through
 * SettingsService into system_settings. Every write is versioned in
 * system_setting_versions and audited — no arbitrary PHP/SQL/JS execution
 * is possible through the catalog.
 */
class SettingCatalog
{
    /**
     * @return array<string, array> key => definition
     */
    public static function definitions(): array
    {
        return [
            /* ------------------------- General ------------------------- */
            'system.app_name' => ['group' => 'General', 'label' => 'Application name', 'type' => 'string', 'default' => 'XERIN GROUP ERP', 'rules' => 'required|string|max:100', 'risk' => 'low'],
            'system.app_short_name' => ['group' => 'General', 'label' => 'Short name', 'type' => 'string', 'default' => 'XerinERP', 'rules' => 'required|string|max:30', 'risk' => 'low'],
            'system.app_description' => ['group' => 'General', 'label' => 'Application description', 'type' => 'string', 'default' => 'Xerin Group Company Limited ERP', 'rules' => 'nullable|string|max:255', 'risk' => 'low'],
            'system.support_email' => ['group' => 'General', 'label' => 'Support email', 'type' => 'string', 'default' => 'support@xerinhub.com', 'rules' => 'nullable|email|max:150', 'risk' => 'low'],
            'system.support_phone' => ['group' => 'General', 'label' => 'Support phone', 'type' => 'string', 'default' => '', 'rules' => 'nullable|string|max:40', 'risk' => 'low'],
            'system.announcement_banner' => ['group' => 'General', 'label' => 'Global banner text', 'type' => 'string', 'default' => '', 'rules' => 'nullable|string|max:255', 'risk' => 'low'],
            'system.read_only_mode' => ['group' => 'General', 'label' => 'Global read-only mode', 'type' => 'bool', 'default' => false, 'rules' => 'boolean', 'risk' => 'high', 'help' => 'Blocks all mutating web requests for non-privileged users.'],
            'system.maintenance_announcement' => ['group' => 'General', 'label' => 'Maintenance announcement', 'type' => 'string', 'default' => '', 'rules' => 'nullable|string|max:255', 'risk' => 'low'],

            /* ------------------------- Regional ------------------------- */
            'system.timezone' => ['group' => 'Regional', 'label' => 'Timezone', 'type' => 'string', 'default' => 'Africa/Dar_es_Salaam', 'rules' => 'required|timezone', 'risk' => 'medium'],
            'system.locale' => ['group' => 'Regional', 'label' => 'Default language', 'type' => 'enum', 'default' => 'en', 'options' => ['en' => 'English', 'sw' => 'Kiswahili', 'fr' => 'Français'], 'rules' => 'required|in:en,sw,fr', 'risk' => 'low'],
            'system.available_locales' => ['group' => 'Regional', 'label' => 'Available languages', 'type' => 'multiselect', 'default' => ['en', 'sw'], 'options' => ['en' => 'English', 'sw' => 'Kiswahili', 'fr' => 'Français'], 'rules' => 'array', 'risk' => 'low'],
            'system.country' => ['group' => 'Regional', 'label' => 'Default country', 'type' => 'string', 'default' => 'Tanzania', 'rules' => 'required|string|max:100', 'risk' => 'low'],
            'system.currency' => ['group' => 'Regional', 'label' => 'Default currency', 'type' => 'enum', 'default' => 'TZS', 'options' => ['TZS' => 'TZS — Tanzanian Shilling', 'USD' => 'USD — US Dollar', 'KES' => 'KES — Kenyan Shilling', 'EUR' => 'EUR — Euro'], 'rules' => 'required|in:TZS,USD,KES,EUR', 'risk' => 'high', 'help' => 'Display default only — does NOT convert historical financial records.'],
            'system.date_format' => ['group' => 'Regional', 'label' => 'Date format', 'type' => 'enum', 'default' => 'Y-m-d', 'options' => ['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'd M Y' => 'D Mon YYYY'], 'rules' => 'required|in:Y-m-d,d/m/Y,m/d/Y,d M Y', 'risk' => 'medium'],
            'system.time_format' => ['group' => 'Regional', 'label' => 'Time format', 'type' => 'enum', 'default' => 'H:i', 'options' => ['H:i' => '24-hour', 'h:i A' => '12-hour'], 'rules' => 'required|in:H:i,h:i A', 'risk' => 'low'],
            'system.first_day_of_week' => ['group' => 'Regional', 'label' => 'First day of week', 'type' => 'enum', 'default' => 'monday', 'options' => ['monday' => 'Monday', 'sunday' => 'Sunday'], 'rules' => 'required|in:monday,sunday', 'risk' => 'low'],

            /* ------------------------- Display ------------------------- */
            'system.records_per_page' => ['group' => 'Display', 'label' => 'Default records per page', 'type' => 'enum', 'default' => 15, 'options' => [10 => '10', 15 => '15', 25 => '25', 50 => '50', 100 => '100'], 'rules' => 'required|integer|in:10,15,25,50,100', 'risk' => 'low'],
            'system.sidebar_collapsed' => ['group' => 'Display', 'label' => 'Sidebar collapsed by default', 'type' => 'bool', 'default' => false, 'rules' => 'boolean', 'risk' => 'low'],
            'system.theme' => ['group' => 'Display', 'label' => 'Default theme', 'type' => 'enum', 'default' => 'light', 'options' => ['light' => 'Light', 'dark' => 'Dark (future)', 'system' => 'Follow system'], 'rules' => 'required|in:light,dark,system', 'risk' => 'low'],

            /* ------------------------- Branding identity ------------------------- */
            'branding.org_name' => ['group' => 'Branding', 'label' => 'Organization name', 'type' => 'string', 'default' => 'Xerin Group Company Limited', 'rules' => 'required|string|max:150', 'risk' => 'low'],
            'branding.product_name' => ['group' => 'Branding', 'label' => 'Product name', 'type' => 'string', 'default' => 'XERIN GROUP ERP', 'rules' => 'required|string|max:100', 'risk' => 'low'],
            'branding.address' => ['group' => 'Branding', 'label' => 'Organization address', 'type' => 'string', 'default' => 'Dar es Salaam, Tanzania', 'rules' => 'nullable|string|max:255', 'risk' => 'low'],
            'branding.email' => ['group' => 'Branding', 'label' => 'Official email', 'type' => 'string', 'default' => 'info@xerinhub.com', 'rules' => 'nullable|email|max:150', 'risk' => 'low'],
            'branding.phone' => ['group' => 'Branding', 'label' => 'Official phone', 'type' => 'string', 'default' => '', 'rules' => 'nullable|string|max:40', 'risk' => 'low'],
            'branding.website' => ['group' => 'Branding', 'label' => 'Website', 'type' => 'string', 'default' => '', 'rules' => 'nullable|url|max:150', 'risk' => 'low'],
            'branding.primary_color' => ['group' => 'Branding', 'label' => 'Primary color', 'type' => 'color', 'default' => '#4f46e5', 'rules' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'risk' => 'low'],
            'branding.accent_color' => ['group' => 'Branding', 'label' => 'Accent color', 'type' => 'color', 'default' => '#6366f1', 'rules' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'risk' => 'low'],
            'branding.footer_text' => ['group' => 'Branding', 'label' => 'Footer text', 'type' => 'string', 'default' => 'Xerin Group Company Limited. All Rights Reserved.', 'rules' => 'nullable|string|max:255', 'risk' => 'low'],
            'branding.footer_year_override' => ['group' => 'Branding', 'label' => 'Footer year override (blank = current year)', 'type' => 'string', 'default' => '', 'rules' => 'nullable|digits:4', 'risk' => 'low'],
            'branding.privacy_url' => ['group' => 'Branding', 'label' => 'Privacy policy link', 'type' => 'string', 'default' => '', 'rules' => 'nullable|url|max:255', 'risk' => 'low'],
            'branding.terms_url' => ['group' => 'Branding', 'label' => 'Terms link', 'type' => 'string', 'default' => '', 'rules' => 'nullable|url|max:255', 'risk' => 'low'],
            'branding.login_heading' => ['group' => 'Branding', 'label' => 'Login page heading', 'type' => 'string', 'default' => 'Welcome back', 'rules' => 'nullable|string|max:100', 'risk' => 'low'],
            'branding.login_subtext' => ['group' => 'Branding', 'label' => 'Login page subtext', 'type' => 'string', 'default' => 'Sign in to your Xerin Group ERP account', 'rules' => 'nullable|string|max:255', 'risk' => 'low'],
            'branding.registration_enabled' => ['group' => 'Branding', 'label' => 'Show register link on login page', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'medium'],

            /* ------------------------- Rules: Authentication ------------------------- */
            'rules.auth.max_login_attempts' => ['group' => 'Rules — Authentication', 'label' => 'Max failed login attempts before lockout', 'type' => 'int', 'default' => 5, 'rules' => 'required|integer|min:3|max:20', 'risk' => 'high'],
            'rules.auth.lockout_minutes' => ['group' => 'Rules — Authentication', 'label' => 'Lockout duration (minutes)', 'type' => 'int', 'default' => 15, 'rules' => 'required|integer|min:1|max:1440', 'risk' => 'high'],
            'rules.auth.reset_token_minutes' => ['group' => 'Rules — Authentication', 'label' => 'Password reset token expiry (minutes)', 'type' => 'int', 'default' => 60, 'rules' => 'required|integer|min:15|max:1440', 'risk' => 'medium'],
            'rules.auth.suspicious_login_alert' => ['group' => 'Rules — Authentication', 'label' => 'Alert on suspicious logins', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'low'],

            /* ------------------------- Rules: Password ------------------------- */
            'rules.password.min_length' => ['group' => 'Rules — Password', 'label' => 'Minimum password length', 'type' => 'int', 'default' => 8, 'rules' => 'required|integer|min:6|max:64', 'risk' => 'medium'],
            'rules.password.require_mixed' => ['group' => 'Rules — Password', 'label' => 'Require mixed case + number', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'medium'],
            'rules.password.expiry_days' => ['group' => 'Rules — Password', 'label' => 'Password expiry (days, 0 = never)', 'type' => 'int', 'default' => 0, 'rules' => 'required|integer|min:0|max:365', 'risk' => 'medium'],
            'rules.password.history' => ['group' => 'Rules — Password', 'label' => 'Prevent reuse of last N passwords', 'type' => 'int', 'default' => 0, 'rules' => 'required|integer|min:0|max:24', 'risk' => 'medium'],

            /* ------------------------- Rules: Session ------------------------- */
            'rules.session.lifetime_minutes' => ['group' => 'Rules — Session', 'label' => 'Session lifetime (minutes)', 'type' => 'int', 'default' => 120, 'rules' => 'required|integer|min:5|max:1440', 'risk' => 'high'],
            'rules.session.max_concurrent' => ['group' => 'Rules — Session', 'label' => 'Max concurrent sessions per user (0 = unlimited)', 'type' => 'int', 'default' => 0, 'rules' => 'required|integer|min:0|max:10', 'risk' => 'high'],
            'rules.session.revoke_on_suspend' => ['group' => 'Rules — Session', 'label' => 'Revoke sessions immediately on suspension', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'high'],

            /* ------------------------- Rules: Registration ------------------------- */
            'rules.registration.public_enabled' => ['group' => 'Rules — Registration', 'label' => 'Public registration applications enabled', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'high'],
            'rules.registration.require_phone' => ['group' => 'Rules — Registration', 'label' => 'Phone number required', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'low'],
            'rules.registration.allowed_domains' => ['group' => 'Rules — Registration', 'label' => 'Allowed email domains (comma-separated, blank = all)', 'type' => 'string', 'default' => '', 'rules' => 'nullable|string|max:500', 'risk' => 'medium'],
            'rules.registration.require_it_approval' => ['group' => 'Rules — Registration', 'label' => 'Require IT approval before provisioning', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'critical', 'help' => 'Cannot be disabled — public applicants never self-provision.', 'locked' => true],

            /* ------------------------- Rules: Operational ------------------------- */
            'rules.files.max_upload_kb' => ['group' => 'Rules — Operational', 'label' => 'Max upload size (KB)', 'type' => 'int', 'default' => 10240, 'rules' => 'required|integer|min:128|max:102400', 'risk' => 'medium'],
            'rules.api.rate_limit' => ['group' => 'Rules — Operational', 'label' => 'API rate limit (requests per minute)', 'type' => 'int', 'default' => 60, 'rules' => 'required|integer|min:10|max:1000', 'risk' => 'high'],
            'rules.retention.audit_days' => ['group' => 'Rules — Operational', 'label' => 'Audit log retention (days, 0 = keep forever)', 'type' => 'int', 'default' => 365, 'rules' => 'required|integer|min:0|max:3650', 'risk' => 'medium'],
            'rules.queue.max_retries' => ['group' => 'Rules — Operational', 'label' => 'Max queue job retries', 'type' => 'int', 'default' => 3, 'rules' => 'required|integer|min:0|max:10', 'risk' => 'medium'],
        ];
    }

    public static function groups(): array
    {
        return collect(self::definitions())->groupBy('group')->map->keys()->all();
    }

    public function __construct(private SettingsService $settings) {}

    public function get(string $key): mixed
    {
        $def = self::definitions()[$key] ?? null;
        $value = $this->settings->get($key, $def['default'] ?? null);

        return match ($def['type'] ?? 'string') {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            'multiselect', 'json' => is_array($value) ? $value : (json_decode((string) $value, true) ?: []),
            default => $value,
        };
    }

    /**
     * Validate and persist a batch of setting updates.
     *
     * @param  array<string,mixed>  $input  key => raw value
     * @return string[] keys actually changed
     */
    public function updateMany(array $input, ?string $reason = null): array
    {
        $defs = self::definitions();
        $changed = [];

        foreach ($input as $key => $value) {
            $def = $defs[$key] ?? null;
            if (! $def || ($def['locked'] ?? false)) {
                continue;
            }

            $validator = Validator::make(['value' => $value], ['value' => $def['rules']]);
            if ($validator->fails()) {
                throw ValidationException::withMessages([$key => $validator->errors()->first('value')]);
            }

            $normalized = $this->normalize($value, $def['type']);
            $old = $this->settings->get($key);

            if ((string) $old === (string) $normalized) {
                continue;
            }

            $type = match ($def['type']) {
                'bool' => 'bool',
                'int' => 'int',
                'float' => 'float',
                'multiselect', 'json' => 'json',
                default => 'string',
            };
            $storeValue = in_array($type, ['json']) ? json_encode($normalized) : $normalized;

            $this->settings->set($key, $storeValue, explode('.', $key)[0], $type);

            SettingVersion::create([
                'key' => $key,
                'group' => $def['group'],
                'old_value' => is_scalar($old) ? (string) $old : json_encode($old),
                'new_value' => is_scalar($normalized) ? (string) $normalized : json_encode($normalized),
                'risk' => $def['risk'],
                'changed_by' => auth()->id(),
                'reason' => $reason,
            ]);

            $changed[] = $key;
        }

        return $changed;
    }

    private function normalize(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            'multiselect' => array_values(array_filter((array) $value)),
            default => $value,
        };
    }
}
