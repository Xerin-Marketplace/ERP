<x-app-layout>
    <x-slot name="header">{{ __('Fixed Assets') }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr><th class="p-3">Ref</th><th class="p-3">Name</th><th class="p-3">Category</th><th class="p-3">Acquired</th><th class="p-3 text-right">Cost</th><th class="p-3 text-right">Acc. Dep.</th><th class="p-3 text-right">NBV</th><th class="p-3">Status</th></tr></thead>
            <tbody>
            @foreach ($assets as $a)
                <tr class="border-t">
                    <td class="p-3 font-mono text-xs">{{ $a->reference }}</td>
                    <td class="p-3">{{ $a->name }}</td>
                    <td class="p-3">{{ $a->category->name }}</td>
                    <td class="p-3">{{ $a->acquired_on->format('d M Y') }}</td>
                    <td class="p-3 text-right">{{ number_format($a->cost, 2) }}</td>
                    <td class="p-3 text-right">{{ number_format($a->accumulated_depreciation, 2) }}</td>
                    <td class="p-3 text-right font-semibold">{{ number_format($a->cost - $a->accumulated_depreciation - $a->residual_value, 2) }}</td>
                    <td class="p-3"><span class="rounded px-2 py-0.5 text-xs bg-gray-100">{{ $a->status }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
