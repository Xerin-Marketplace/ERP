<x-app-layout>
    <x-slot name="header">{{ __('Organization Audit History') }}</x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Resource</label>
            <select name="resource" class="rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach ($resourceTypes as $t)
                    <option value="{{ $t }}" @selected(request('resource') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Action</label>
            <input type="text" name="action" value="{{ request('action') }}" placeholder="e.g. created"
                   class="rounded-md border-gray-300 text-sm shadow-sm">
        </div>
        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
        @if (request()->hasAny(['resource', 'action', 'user']))
            <a href="{{ route('organization.audit') }}" class="text-sm text-gray-500 hover:underline">Reset</a>
        @endif
    </form>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Actor</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Resource</th>
                    <th class="px-4 py-3">Changes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-gray-500" title="{{ $log->created_at }}">
                            {{ $log->created_at->diffForHumans(short: true) }}
                        </td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3">
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ $log->action }}</code>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ class_basename($log->resource_type ?? '') }} #{{ $log->resource_id }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400">
                            @if ($log->new_values)
                                {{ collect($log->new_values)->keys()->take(4)->join(', ') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No audit records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>
