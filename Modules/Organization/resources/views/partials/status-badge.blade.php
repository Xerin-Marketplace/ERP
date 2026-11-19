@php
    $val = $status instanceof \Modules\Organization\Enums\EntityStatus ? $status->value : $status;
    $colors = [
        'active' => 'bg-green-100 text-green-700',
        'suspended' => 'bg-amber-100 text-amber-700',
        'archived' => 'bg-gray-200 text-gray-600',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $colors[$val] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($val) }}</span>
