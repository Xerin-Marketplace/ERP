@php
    $__logo = rescue(fn () => app(\Modules\SystemAdministration\Services\BrandingService::class)->currentUrl('logo_primary'), null, report: false);
@endphp
<img src="{{ $__logo ?? asset('xerin-logo.webp') }}" alt="{{ config('app.name', 'Xerin Group') }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
