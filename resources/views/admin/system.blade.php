<x-app-layout>
    <x-slot name="header">
        {{ __('System') }}
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Application info -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-900">Application</h2>
            </div>
            <dl class="divide-y divide-gray-100">
                @foreach ($info as $label => $value)
                    <div class="flex items-center justify-between px-5 py-2.5">
                        <dt class="text-sm text-gray-500">{{ $label }}</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <!-- Health -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-900">System health</h2>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach ($health as $service => $status)
                    <li class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-gray-600">{{ $service }}</span>
                        @if ($status === 'ok')
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-green-700 bg-green-50 px-2 py-1 rounded-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Operational
                            </span>
                        @elseif ($status === 'fail')
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 bg-red-50 px-2 py-1 rounded-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed
                            </span>
                        @else
                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-sm">{{ $status }}</span>
                        @endif
                    </li>
                @endforeach
                <li class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-600">Users</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $counts['users'] }}</span>
                </li>
                <li class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-600">Audit events</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $counts['audit_events'] }}</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Audit log -->
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Recent audit events</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b border-gray-200">
                        <th class="px-5 py-3 font-medium">Actor</th>
                        <th class="px-5 py-3 font-medium">Action</th>
                        <th class="px-5 py-3 font-medium">Resource</th>
                        <th class="px-5 py-3 font-medium">IP</th>
                        <th class="px-5 py-3 font-medium">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentAudit as $log)
                        <tr>
                            <td class="px-5 py-3 text-gray-900">{{ $log->user?->name ?? 'system' }}</td>
                            <td class="px-5 py-3"><span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded-sm">{{ $log->action }}</span></td>
                            <td class="px-5 py-3 text-gray-500">{{ $log->resource_type ? class_basename($log->resource_type).'#'.$log->resource_id : '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No audit events yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
