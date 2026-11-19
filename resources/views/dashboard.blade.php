<x-app-layout>
    <x-slot name="header">
        {{ __('Dashboard') }}
    </x-slot>

    <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 800)">

        <!-- Welcome -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Here's what's happening in your business today.</p>
        </div>

        <!-- Stats: skeleton while loading -->
        <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="rounded-sm border border-gray-200 bg-white p-5"><div class="skeleton h-4 w-24"></div><div class="skeleton h-8 w-32 mt-4"></div><div class="skeleton h-3 w-20 mt-3"></div></div>
            <div class="rounded-sm border border-gray-200 bg-white p-5"><div class="skeleton h-4 w-24"></div><div class="skeleton h-8 w-32 mt-4"></div><div class="skeleton h-3 w-20 mt-3"></div></div>
            <div class="rounded-sm border border-gray-200 bg-white p-5"><div class="skeleton h-4 w-24"></div><div class="skeleton h-8 w-32 mt-4"></div><div class="skeleton h-3 w-20 mt-3"></div></div>
            <div class="rounded-sm border border-gray-200 bg-white p-5"><div class="skeleton h-4 w-24"></div><div class="skeleton h-8 w-32 mt-4"></div><div class="skeleton h-3 w-20 mt-3"></div></div>
        </div>

        <div x-show="!loading" x-transition.opacity.duration.300ms x-cloak>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                @php
                    $stats = [
                        ['label' => 'Total Revenue', 'value' => 'TSh 8.4M', 'sub' => '+18% vs last month', 'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Orders', 'value' => '1,284', 'sub' => '+9% vs last month', 'icon' => 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'],
                        ['label' => 'Products', 'value' => '342', 'sub' => '12 low stock', 'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25 4.5-4.5m-12.75-9h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
                        ['label' => 'Customers', 'value' => '97', 'sub' => '+6 this week', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z'],
                    ];
                @endphp

                @foreach ($stats as $stat)
                    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm animate-fade-up">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                            <span class="flex items-center justify-center w-8 h-8 rounded-sm bg-gray-100 text-gray-500">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ $stat['sub'] }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Sales overview</h2>
                        <span class="text-xs font-medium text-gray-400">Last 7 days</span>
                    </div>
                    <div class="p-5">
                        <div class="relative h-64"><canvas id="salesChart"></canvas></div>
                    </div>
                </div>

                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Orders by status</h2>
                    </div>
                    <div class="p-5">
                        <div class="relative h-52"><canvas id="ordersChart"></canvas></div>
                        <div class="mt-4 space-y-1.5">
                            <div class="flex items-center gap-2 text-xs text-gray-600"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-600"></span>Completed — 62%</div>
                            <div class="flex items-center gap-2 text-xs text-gray-600"><span class="w-2.5 h-2.5 rounded-sm bg-gray-400"></span>Pending — 27%</div>
                            <div class="flex items-center gap-2 text-xs text-gray-600"><span class="w-2.5 h-2.5 rounded-sm bg-gray-200"></span>Cancelled — 11%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity + actions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Recent activity</h2>
                        <button class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all</button>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @foreach ([
                            ['text' => 'New order #1284 placed by Juma Hassan', 'time' => '5 min ago'],
                            ['text' => 'Payment received — TSh 240,000', 'time' => '32 min ago'],
                            ['text' => 'Product "Cement 50kg" is low on stock', 'time' => '1 hr ago'],
                            ['text' => 'New customer registered — Amina Juma', 'time' => '3 hrs ago'],
                        ] as $activity)
                            <li class="flex items-center gap-3 px-5 py-3.5">
                                <span class="w-2 h-2 rounded-full bg-gray-300 shrink-0"></span>
                                <p class="flex-1 text-sm text-gray-700">{{ $activity['text'] }}</p>
                                <span class="text-xs text-gray-400 shrink-0">{{ $activity['time'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Quick actions</h2>
                    </div>
                    <div class="p-3 space-y-1">
                        @foreach ([
                            ['label' => 'New sale', 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
                            ['label' => 'Add product', 'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6m3-3H9'],
                            ['label' => 'Add customer', 'icon' => 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z'],
                            ['label' => 'View reports', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
                        ] as $action)
                            <a href="#" class="flex items-center gap-3 rounded-sm px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                <span class="flex items-center justify-center w-8 h-8 rounded-sm bg-gray-100 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}" />
                                    </svg>
                                </span>
                                {{ $action['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('salesChart'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Sales (TSh 000)',
                        data: [420, 680, 510, 790, 940, 720, 1050],
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#4f46e5',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } },
                        y: { border: { display: false }, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 11 } } }
                    }
                }
            });

            new Chart(document.getElementById('ordersChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [62, 27, 11],
                        backgroundColor: ['#4f46e5', '#9ca3af', '#e5e7eb'],
                        borderWidth: 0,
                        cutout: '72%',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        });
    </script>
</x-app-layout>
