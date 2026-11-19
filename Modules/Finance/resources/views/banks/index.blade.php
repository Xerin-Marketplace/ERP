<x-app-layout>
    <x-slot name="header">{{ __('Bank & Cash Management') }}</x-slot>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($accounts as $row)
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex justify-between items-start">
                <div><h3 class="font-semibold">{{ $row['account']->name }}</h3><p class="text-xs text-gray-500">{{ $row['account']->bank_name }} · {{ $row['account']->currency }}</p></div>
                <span class="text-xs {{ $row['account']->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $row['account']->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <dl class="mt-3 text-sm space-y-1">
                <div class="flex justify-between"><dt class="text-gray-500">Book balance</dt><dd class="font-bold">{{ number_format($row['book'], 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Statement balance</dt><dd>{{ number_format($row['statement'], 2) }}</dd></div>
                <div class="flex justify-between border-t pt-1"><dt class="text-gray-500">Difference</dt><dd class="{{ abs($row['book'] - $row['statement']) > 0.01 ? 'text-red-600 font-bold' : 'text-green-600' }}">{{ number_format($row['statement'] - $row['book'], 2) }}</dd></div>
            </dl>
        </div>
        @endforeach
    </div>
</x-app-layout>
