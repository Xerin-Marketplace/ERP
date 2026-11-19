<x-app-layout>
    <x-slot name="header">{{ __('Workforce Dashboard') }}</x-slot>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @php
            $cards = [
                ['Total Employees', $metrics['total'], 'text-gray-900'],
                ['Active', $metrics['active'], 'text-green-600'],
                ['Onboarding', $metrics['onboarding'], 'text-blue-600'],
                ['Separated', $metrics['separated'], 'text-gray-500'],
                ['On Probation', $metrics['probation'], 'text-amber-600'],
                ['Contract', $metrics['contract'], 'text-indigo-600'],
                ['Contracts Expiring (60d)', $metrics['contracts_expiring'], 'text-red-600'],
                ['Pending Transfers', $metrics['pending_transfers'], 'text-purple-600'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $color])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Distribution by Company</h3>
            <div class="space-y-2">
                @forelse ($metrics['by_company'] as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-700">{{ $row['name'] }}</span>
                        <span class="font-semibold text-gray-900">{{ $row['total'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No placements yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Distribution by Department</h3>
            <div class="space-y-2">
                @forelse ($metrics['by_department'] as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-700">{{ $row['name'] }}</span>
                        <span class="font-semibold text-gray-900">{{ $row['total'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No placements yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Recent Changes</h3>
            <div class="space-y-2 text-sm">
                @forelse ($metrics['recent_transfers']->merge($metrics['recent_promotions'])->take(6) as $item)
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-700 truncate">{{ $item->employee?->full_name ?? '—' }}</span>
                        <span class="text-xs text-gray-400 shrink-0">{{ $item->type ?? 'transfer' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No recent changes.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
