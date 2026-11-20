<x-app-layout>
    <x-slot name="header">{{ __('Security Center') }}</x-slot>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
        <!-- Active sessions -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Active sessions</h2></div>
            <ul class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @forelse ($sessions as $s)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">{{ $sessionUsers[$s->user_id] ?? 'Guest' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $s->ip_address }} · {{ substr((string) $s->user_agent, 0, 60) }} · {{ \Illuminate\Support\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</p>
                        </div>
                        @if ($s->user_id && $s->id !== session()->getId())
                            <form method="POST" action="{{ route('systemadmin.security.sessions.revoke') }}" onsubmit="return confirm('Revoke this session?')">
                                @csrf
                                <input type="hidden" name="session_id" value="{{ $s->id }}">
                                <button class="rounded-md bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100">Revoke</button>
                            </form>
                        @elseif ($s->id === session()->getId())
                            <span class="text-[10px] font-semibold uppercase text-green-600">current</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No sessions.</li>
                @endforelse
            </ul>
        </div>

        <!-- Security events -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Security events</h2></div>
            <ul class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @forelse ($securityEvents as $e)
                    <li class="px-5 py-3">
                        <p class="text-sm text-gray-700"><span class="font-medium">{{ $e->user?->name ?? 'System' }}</span> — <span class="font-mono text-xs">{{ $e->event_type ?? 'event' }}</span></p>
                        <p class="text-xs text-gray-400">{{ $e->ip_address ?? '' }} · {{ $e->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No security events.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Login history -->
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Login history</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b border-gray-200">
                    <th class="px-5 py-3 font-medium">User</th><th class="px-5 py-3 font-medium">Result</th>
                    <th class="px-5 py-3 font-medium">IP</th><th class="px-5 py-3 font-medium">Time</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($loginHistory as $h)
                        <tr>
                            <td class="px-5 py-3 text-gray-900">{{ $h->user?->name ?? $h->email ?? '—' }}</td>
                            <td class="px-5 py-3"><span class="rounded-sm px-2 py-0.5 text-[10px] font-semibold uppercase {{ $h->success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ $h->success ? 'success' : 'failed' }}</span></td>
                            <td class="px-5 py-3 text-gray-500">{{ $h->ip_address ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-400">{{ $h->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No login history.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
