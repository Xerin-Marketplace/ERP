<x-app-layout>
    <x-slot name="header">{{ __('System Monitoring') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Health probes</h2></div>
            <ul class="divide-y divide-gray-100">
                @foreach ($probes as $name => $probe)
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm capitalize text-gray-600">{{ $name }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">{{ $probe['detail'] }}</span>
                            <span class="w-2 h-2 rounded-full {{ $probe['status'] === 'ok' ? 'bg-green-500' : ($probe['status'] === 'warn' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                        </span>
                    </li>
                @endforeach
                <li class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-sm text-gray-600">Active sessions (15 min)</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $activeSessions }}</span>
                </li>
                <li class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-sm text-gray-600">Pending queue jobs</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $pendingJobs }}</span>
                </li>
            </ul>
        </div>

        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Failed jobs</h2>
                <span class="text-xs font-semibold {{ $failedJobs->isNotEmpty() ? 'text-red-600' : 'text-gray-400' }}">{{ $failedJobs->count() }} shown</span>
            </div>
            <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                @forelse ($failedJobs as $job)
                    <li class="px-5 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs text-gray-800 truncate">{{ $job->payload ? json_decode($job->payload, true)['displayName'] ?? 'job' : 'job' }}</p>
                                <p class="text-xs text-gray-400">{{ $job->queue }} · {{ $job->failed_at }}</p>
                                <p class="mt-0.5 text-xs text-red-500 truncate">{{ \Illuminate\Support\Str::limit($job->exception, 120) }}</p>
                            </div>
                            <form method="POST" action="{{ route('systemadmin.monitoring.jobs.retry', $job->id) }}">@csrf
                                <button class="shrink-0 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">Retry</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No failed jobs.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
