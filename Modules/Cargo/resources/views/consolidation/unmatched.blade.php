<x-app-layout>
    <x-slot name="header">Unmatched Parcels</x-slot>
    <p class="mb-4 text-sm text-gray-500">Received parcels with no customer or booking — match before consolidation (spec 9.3).</p>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Package</th><th class="px-4 py-2">Received</th><th class="px-4 py-2">Description</th><th class="px-4 py-2">KG</th><th class="px-4 py-2">Status</th></tr></thead>
            <tbody>
            @forelse ($packages as $p)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $p->reference }}</td>
                    <td class="px-4 py-2 text-xs">{{ $p->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-2 text-xs">{{ \Illuminate\Support\Str::limit($p->goods_description, 60) }}</td>
                    <td class="px-4 py-2">{{ $p->gross_weight }}</td>
                    <td class="px-4 py-2"><span class="rounded-sm bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">{{ $p->status }}</span></td>
                </tr>
            @empty <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Queue empty.</td></tr> @endforelse
            </tbody></table>
        <div class="p-4">{{ $packages->links() }}</div>
    </div>
</x-app-layout>
