@php
    $__branding = app(\Modules\SystemAdministration\Services\BrandingService::class);
    $__settings = app(\App\Services\SettingsService::class);
    $__primary = $__settings->get('branding.primary_color', '#4f46e5');
    $__accent = $__settings->get('branding.accent_color', '#6366f1');
@endphp
<link rel="icon" type="image/png" href="{{ $__branding->currentUrl('favicon') ?? asset('favicon.png') }}">
<style>
    :root { --sa-primary: {{ $__primary }}; --sa-accent: {{ $__accent }}; }
    /* System > Branding theme tokens — only accent surfaces are tinted. */
    .bg-indigo-600, .hover\:bg-indigo-700:hover, .hover\:bg-indigo-500:hover { background-color: var(--sa-primary) !important; }
    .text-indigo-600 { color: var(--sa-primary) !important; }
    .hover\:text-indigo-500:hover { color: var(--sa-accent) !important; }
    .bg-indigo-500 { background-color: var(--sa-accent) !important; }
    .focus\:border-indigo-500:focus { border-color: var(--sa-primary) !important; }
    .focus\:ring-indigo-500:focus { --tw-ring-color: var(--sa-primary) !important; }
    .bg-indigo-400 { background-color: var(--sa-accent) !important; }
</style>
