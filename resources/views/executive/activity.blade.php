<x-app-layout>
    <x-slot name="header">Activity &amp; Audit Stream</x-slot>
    <form method="GET" class="mb-4 flex gap-3">
        <input name="action" value="{{ request('action') }}" placeholder="Filter by action prefix (e.g. finance., cargo.)" class="rounded-sm border-gray-300 text-sm w-80">
        <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-gray-500"><tr class="border-b">
                <th class="p-3">Time</th><th class="p-3">User</th><th class="p-3">Action</th><th class="p-3">Resource</th><th class="p-3">IP</th>
            </tr></thead>
            <tbody>
            @forelse ($activity as $a)
                <tr class="border-t">
                    <td class="p-3 text-xs whitespace-nowrap">{{ $a->created_at->format('Y-m-d H:i') }}</td>
                    <td class="p-3">{{ $a->user->name ?? 'System' }}</td>
                    <td class="p-3 font-mono text-xs">{{ $a->action }}</td>
                    <td class="p-3 text-xs text-gray-500">{{ $a->resource_type }} #{{ $a->resource_id }}</td>
                    <td class="p-3 text-xs text-gray-400">{{ $a->ip_address ?? '—' }}</td>
                </tr>
            @empty <tr><td colspan="5" class="p-6 text-center text-gray-400">No audit events.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-gray-400">Audit log is append-only — entries cannot be edited through the application.</p>
</x-app-layout>
