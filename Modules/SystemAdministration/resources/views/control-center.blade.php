<x-app-layout>
    <x-slot name="header">{{ __('Control Center') }}</x-slot>

    <!-- Platform metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
        @foreach ([
            ['Users', $platform['users_total'], $platform['users_active'].' active'],
            ['Suspended', $platform['users_suspended'], 'accounts'],
            ['Registrations', $platform['registrations_pending'], 'pending review'],
            ['Companies', $platform['companies'], $platform['branches'].' branches'],
            ['Departments', $platform['departments'], 'org-wide'],
            ['Sessions', $platform['sessions_active'], 'active now'],
        ] as $card)
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $card[0] }}</p>
                <p class="mt-1.5 text-2xl font-bold text-gray-900">{{ $card[1] }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ $card[2] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- Health -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">System health</h2></div>
            <ul class="divide-y divide-gray-100">
                @foreach ($health as $name => $probe)
                    <li class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm capitalize text-gray-600">{{ $name }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">{{ $probe['detail'] }}</span>
                            <span class="w-2 h-2 rounded-full {{ $probe['status'] === 'ok' ? 'bg-green-500' : ($probe['status'] === 'warn' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                        </span>
                    </li>
                @endforeach
                <li class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-600">Failed logins (24h)</span>
                    <span class="text-sm font-semibold {{ $platform['failed_logins_24h'] > 10 ? 'text-red-600' : 'text-gray-900' }}">{{ $platform['failed_logins_24h'] }}</span>
                </li>
            </ul>
        </div>

        <!-- Security -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Security events</h2></div>
            <ul class="divide-y divide-gray-100">
                @forelse ($security['recent_events'] as $e)
                    <li class="px-5 py-3">
                        <p class="text-sm text-gray-700"><span class="font-medium">{{ $e->user?->name ?? 'System' }}</span> — {{ str_replace('_', ' ', $e->event_type ?? $e->action ?? 'event') }}</p>
                        <p class="text-xs text-gray-400">{{ $e->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No security events recorded.</li>
                @endforelse
            </ul>
        </div>

        <!-- Activity -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Administrative activity</h2></div>
            <ul class="divide-y divide-gray-100">
                @forelse ($activity as $log)
                    <li class="px-5 py-3">
                        <p class="text-sm text-gray-700"><span class="font-medium">{{ $log->user?->name ?? 'system' }}</span> <span class="font-mono text-xs text-gray-500">{{ $log->action }}</span></p>
                        <p class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No activity recorded yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Administration</h2></div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 p-4">
            @foreach ([
                ['Branding', 'systemadmin.branding', 'sysadmin.branding.manage'],
                ['General Settings', 'systemadmin.settings', 'sysadmin.settings.manage'],
                ['System Rules', 'systemadmin.rules', 'sysadmin.rules.manage'],
                ['Modules', 'systemadmin.modules', 'sysadmin.modules.manage'],
                ['Navigation', 'systemadmin.navigation', 'sysadmin.navigation.manage'],
                ['Feature Flags', 'systemadmin.flags', 'sysadmin.flags.manage'],
                ['Announcements', 'systemadmin.announcements', 'sysadmin.announcements.manage'],
                ['Integrations', 'systemadmin.integrations', 'sysadmin.integrations.manage'],
                ['Security', 'systemadmin.security', 'sysadmin.security.manage'],
                ['Audit Logs', 'systemadmin.audit', 'sysadmin.audit.view'],
                ['Maintenance', 'systemadmin.maintenance', 'sysadmin.maintenance.manage'],
                ['Monitoring', 'systemadmin.monitoring', 'sysadmin.monitoring.view'],
                ['Localization', 'systemadmin.localization', 'sysadmin.localization.manage'],
                ['Backups', 'systemadmin.backups', 'sysadmin.backups.view'],
            ] as [$label, $route, $perm])
                @can($perm)
                    <a href="{{ route($route, $route === 'systemadmin.settings' ? 'General' : []) }}" class="rounded-md border border-gray-200 px-3 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors">{{ $label }}</a>
                @endcan
            @endforeach
        </div>
    </div>
</x-app-layout>
