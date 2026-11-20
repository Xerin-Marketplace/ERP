@php
    // Cached as plain arrays — the database cache store cannot safely
    // serialize Eloquent collections across requests.
    $__announcements = collect(cache()->remember('sys:announcements', 60, function () {
        try {
            return \Modules\SystemAdministration\Models\Announcement::live()->latest()->limit(3)->get()
                ->map->only(['id', 'title', 'body', 'level', 'audience', 'requires_ack'])->all();
        } catch (\Throwable) {
            return [];
        }
    }))->map(fn ($a) => (object) $a)
      ->filter(fn ($a) => $a->audience === 'all' || auth()->user()?->can('sysadmin.dashboard.view'));
    $__banner = cache()->remember('sys:banner', 60, fn () => rescue(fn () => app(\App\Services\SettingsService::class)->get('system.announcement_banner'), '', report: false));
@endphp

@if ($__banner)
    <div class="bg-gray-900 px-4 py-2 text-center text-xs font-medium text-white">{{ $__banner }}</div>
@endif

@foreach ($__announcements as $a)
    <div x-data="{ show: localStorage.getItem('ack_{{ $a->id }}') !== '1' || {{ $a->requires_ack ? 'true' : 'false' }} }" x-show="show" x-cloak
         class="border-b px-4 py-2.5 text-sm {{ $a->level === 'danger' ? 'bg-red-50 border-red-200 text-red-800' : ($a->level === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-indigo-50 border-indigo-200 text-indigo-800') }}">
        <div class="mx-auto flex max-w-7xl items-center gap-3">
            <span class="font-semibold">{{ $a->title }}</span>
            <span class="flex-1">{{ $a->body }}</span>
            <button x-on:click="localStorage.setItem('ack_{{ $a->id }}', '1'); show = false" class="shrink-0 text-xs font-medium opacity-70 hover:opacity-100">
                {{ $a->requires_ack ? 'Acknowledge' : 'Dismiss' }}
            </button>
        </div>
    </div>
@endforeach
