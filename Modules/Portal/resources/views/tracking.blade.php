<x-app-layout>
    <x-slot name="header">Cargo Tracking</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Package</th><th>Status</th><th>Milestone</th></tr></thead>
        <tbody class="divide-y">@forelse($timeline as $t)
            <tr><td class="p-2 font-mono">{{ $t['package'] }}</td><td>{{ $t['status'] }}</td><td class="capitalize">{{ $t['label'] }}</td></tr>
            @empty<tr><td colspan="3" class="p-6 text-center text-gray-400">No packages on record.</td></tr>@endforelse
        </tbody></table>
    </div>
</x-app-layout>
