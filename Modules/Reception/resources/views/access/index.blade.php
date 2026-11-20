<x-app-layout>
    <x-slot name="header">Access Requests</x-slot>
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Reference / name / email" class="rounded-sm border-gray-300 text-sm">
        <select name="type" class="rounded-sm border-gray-300 text-sm">
            <option value="">All types</option>
            @foreach (['new_employee','existing_employee','external'] as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ str_replace('_',' ',ucfirst($t)) }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-sm border-gray-300 text-sm">
            <option value="">All statuses</option>
            @foreach (\Modules\Reception\Services\AccessRequestService::STATUSES as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_',' ',ucfirst($s)) }}</option>
            @endforeach
        </select>
        <button class="rounded-sm bg-gray-800 px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Reference</th><th class="px-4 py-2">Name</th><th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Branch</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Submitted</th><th></th></tr></thead>
            <tbody>
            @forelse ($requests as $r)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $r->reference }}</td>
                    <td class="px-4 py-2">{{ $r->full_name }}<p class="text-xs text-gray-400">{{ $r->email }}</p></td>
                    <td class="px-4 py-2 text-xs">{{ str_replace('_',' ',$r->type) }}</td>
                    <td class="px-4 py-2 text-xs">{{ $r->branch->name ?? '—' }}</td>
                    <td class="px-4 py-2"><span class="text-xs font-semibold">{{ str_replace('_',' ',ucfirst($r->status)) }}</span></td>
                    <td class="px-4 py-2 text-xs text-gray-400">{{ $r->submitted_at->format('d M Y') }}</td>
                    <td class="px-4 py-2"><a href="{{ route('reception.access.requests.show', $r) }}" class="text-xs font-semibold text-xerin-700">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No requests.</td></tr>
            @endforelse
            </tbody></table>
        <div class="p-4">{{ $requests->links() }}</div>
    </div>
</x-app-layout>
