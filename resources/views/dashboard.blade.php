<x-app-layout>
    <x-slot name="header">
        {{ __('Dashboard') }}
    </x-slot>

    <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 500)">

        <!-- Welcome -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Here's the current state of your organization.</p>
        </div>

        <!-- Stats: skeleton while loading -->
        <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            @for ($i = 0; $i < 4; $i++)
                <div class="rounded-sm border border-gray-200 bg-white p-5"><div class="skeleton h-4 w-24"></div><div class="skeleton h-8 w-32 mt-4"></div><div class="skeleton h-3 w-20 mt-3"></div></div>
            @endfor
        </div>

        <div x-show="!loading" x-transition.opacity.duration.300ms x-cloak>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                @php
                    $cards = [
                        ['label' => 'Companies', 'value' => $stats['companies'], 'sub' => $stats['active_companies'] . ' active', 'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21', 'href' => 'organization.companies.index'],
                        ['label' => 'Branches', 'value' => $stats['branches'], 'sub' => $stats['active_branches'] . ' active · ' . $stats['suspended_branches'] . ' suspended', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z', 'href' => 'organization.branches.index'],
                        ['label' => 'Departments', 'value' => $stats['departments'], 'sub' => $stats['active_departments'] . ' active', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z', 'href' => 'organization.departments.index'],
                        ['label' => 'Users', 'value' => $stats['users'], 'sub' => 'registered accounts', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z', 'href' => null],
                    ];
                @endphp

                @foreach ($cards as $stat)
                    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm animate-fade-up">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                            <span class="flex items-center justify-center w-8 h-8 rounded-sm bg-gray-100 text-gray-500">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-bold text-gray-900">
                            @if ($stat['href'] && Route::has($stat['href']))
                                <a href="{{ route($stat['href']) }}" class="hover:text-indigo-600">{{ $stat['value'] }}</a>
                            @else
                                {{ $stat['value'] }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-gray-400">{{ $stat['sub'] }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Departments by company</h2>
                    </div>
                    <div class="p-5">
                        <div class="relative h-64"><canvas id="departmentsChart"></canvas></div>
                    </div>
                </div>

                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Branches by status</h2>
                    </div>
                    <div class="p-5">
                        <div class="relative h-52"><canvas id="branchesChart"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- Identity blocks: IT queue + personal placement -->
            @if ($identity['registrations'] || $identity['my_assignment'])
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                    @if ($identity['registrations'])
                        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                                <h2 class="font-semibold text-gray-900">Registration queue</h2>
                                <a href="{{ route('identity.registrations.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all</a>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-5">
                                <div class="rounded-md bg-amber-50 border border-amber-200 p-3 text-center">
                                    <p class="text-xl font-bold text-amber-700">{{ $identity['registrations']['pending_review'] }}</p>
                                    <p class="text-xs text-amber-600">Pending review</p>
                                </div>
                                <div class="rounded-md bg-blue-50 border border-blue-200 p-3 text-center">
                                    <p class="text-xl font-bold text-blue-700">{{ $identity['registrations']['under_review'] }}</p>
                                    <p class="text-xs text-blue-600">In review</p>
                                </div>
                                <div class="rounded-md bg-green-50 border border-green-200 p-3 text-center">
                                    <p class="text-xl font-bold text-green-700">{{ $identity['registrations']['approved'] }}</p>
                                    <p class="text-xs text-green-600">Approved</p>
                                </div>
                                <div class="rounded-md bg-gray-50 border border-gray-200 p-3 text-center">
                                    <p class="text-xl font-bold text-gray-700">{{ $identity['registrations']['active'] }}</p>
                                    <p class="text-xs text-gray-500">Activated</p>
                                </div>
                            </div>
                            @if ($identity['pending_apps']->isNotEmpty())
                                <ul class="divide-y divide-gray-100 border-t border-gray-100">
                                    @foreach ($identity['pending_apps'] as $app)
                                        <li class="flex items-center gap-3 px-5 py-3">
                                            <span class="font-mono text-xs text-gray-400">{{ $app->reference }}</span>
                                            <span class="flex-1 text-sm text-gray-700">{{ $app->full_name }}</span>
                                            <a href="{{ route('identity.registrations.show', $app) }}" class="text-sm font-medium text-indigo-600">Review</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    @if ($identity['my_assignment'])
                        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">My placement</h2></div>
                            <dl class="p-5 space-y-2.5 text-sm">
                                <div class="flex justify-between"><dt class="text-gray-500">Company</dt><dd class="font-medium">{{ $identity['my_assignment']->company->legal_name }}</dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Branch</dt><dd>{{ $identity['my_assignment']->branch?->name ?? '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Department</dt><dd>{{ $identity['my_assignment']->department?->name ?? '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Position</dt><dd>{{ $identity['my_assignment']->position ?? '—' }}</dd></div>
                            </dl>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Activity + actions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Recent activity</h2>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse ($activity as $log)
                            <li class="flex items-center gap-3 px-5 py-3.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                                <p class="flex-1 text-sm text-gray-700">
                                    <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                                    {{ str_replace('_', ' ', $log->action) }}
                                    @if ($log->resource_type)
                                        <span class="text-gray-400">— {{ class_basename($log->resource_type) }}</span>
                                    @endif
                                </p>
                                <span class="text-xs text-gray-400 shrink-0">{{ $log->created_at->diffForHumans(short: true) }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center text-sm text-gray-400">No activity recorded yet.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Quick actions</h2>
                    </div>
                    <div class="p-3 space-y-1">
                        @php
                            $actions = [
                                ['label' => 'New company', 'permission' => 'company.create', 'route' => 'organization.companies.create', 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
                                ['label' => 'New branch', 'permission' => 'branch.create', 'route' => 'organization.branches.create', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
                                ['label' => 'New department', 'permission' => 'department.create', 'route' => 'organization.departments.create', 'icon' => 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z'],
                                ['label' => 'Org structure', 'permission' => 'organization.structure.view', 'route' => 'organization.structure', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
                            ];
                        @endphp
                        @foreach ($actions as $action)
                            @can($action['permission'])
                                <a href="{{ route($action['route']) }}" class="flex items-center gap-3 rounded-sm px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-sm bg-gray-100 text-gray-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}" />
                                        </svg>
                                    </span>
                                    {{ $action['label'] }}
                                </a>
                            @endcan
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deptData = @js($stats['departments_by_company']);
            new Chart(document.getElementById('departmentsChart'), {
                type: 'bar',
                data: {
                    labels: Object.keys(deptData),
                    datasets: [{
                        label: 'Departments',
                        data: Object.values(deptData),
                        backgroundColor: '#4f46e5',
                        borderRadius: 4,
                        maxBarThickness: 48,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } },
                        y: { beginAtZero: true, border: { display: false }, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 11 }, precision: 0 } }
                    }
                }
            });

            const branchData = @js($stats['branches_by_status']);
            const statusColors = { active: '#4f46e5', suspended: '#f59e0b', archived: '#9ca3af' };
            const labels = Object.keys(branchData);
            new Chart(document.getElementById('branchesChart'), {
                type: 'doughnut',
                data: {
                    labels: labels.map(s => s[0].toUpperCase() + s.slice(1)),
                    datasets: [{
                        data: Object.values(branchData),
                        backgroundColor: labels.map(s => statusColors[s] || '#9ca3af'),
                        borderWidth: 0,
                        cutout: '72%',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });
    </script>
</x-app-layout>
