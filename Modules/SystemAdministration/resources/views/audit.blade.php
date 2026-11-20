<x-app-layout>
    <x-slot name="header">{{ __('Audit Log Center') }}</x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div><label class="block text-xs font-medium text-gray-600">Search action</label>
            <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="e.g. settings" class="mt-1 w-44 rounded-md border-gray-300 text-sm shadow-sm"></div>
        <div><label class="block text-xs font-medium text-gray-600">User</label>
            <select name="user" class="mt-1 w-44 rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">All users</option>
                @foreach ($users as $id => $name)<option value="{{ $id }}" @selected(($filters['user'] ?? '') == $id)>{{ $name }}</option>@endforeach
            </select></div>
        <div><label class="block text-xs font-medium text-gray-600">Resource</label>
            <input name="resource" type="text" value="{{ $filters['resource'] ?? '' }}" placeholder="e.g. User" class="mt-1 w-36 rounded-md border-gray-300 text-sm shadow-sm"></div>
        <div><label class="block text-xs font-medium text-gray-600">From</label>
            <input name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm"></div>
        <div><label class="block text-xs font-medium text-gray-600">To</label>
            <input name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm"></div>
        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
        <a href="{{ route('systemadmin.audit.export') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Export CSV</a>
    </form>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b border-gray-200">
                    <th class="px-5 py-3 font-medium">Actor</th><th class="px-5 py-3 font-medium">Action</th>
                    <th class="px-5 py-3 font-medium">Resource</th><th class="px-5 py-3 font-medium">Changes</th>
                    <th class="px-5 py-3 font-medium">IP</th><th class="px-5 py-3 font-medium">Time</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-5 py-3 text-gray-900">{{ $log->user?->name ?? 'system' }}</td>
                            <td class="px-5 py-3"><span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded-sm">{{ $log->action }}</span></td>
                            <td class="px-5 py-3 text-gray-500">{{ $log->resource_type ? class_basename($log->resource_type).'#'.$log->resource_id : '—' }}</td>
                            <td class="px-5 py-3 text-xs text-gray-400">
                                @if ($log->new_values){{ \Illuminate\Support\Str::limit(json_encode($log->new_values), 60) }}@else — @endif
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No audit events match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-3">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
