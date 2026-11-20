<x-app-layout>
    <x-slot name="header">{{ __('Command Center') }}</x-slot>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold">{{ $stats['total'] }}</div><div class="text-xs text-gray-500">Commands</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold">{{ $stats['executed'] }}</div><div class="text-xs text-gray-500">Executed</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</div><div class="text-xs text-gray-500">Failed</div></div>
    </div>

    @if ($pending->count())
    <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 mb-6">
        <h3 class="font-semibold text-amber-800 mb-3">Pending Approvals ({{ $pending->count() }})</h3>
        @foreach ($pending as $e)
        <div class="flex items-center justify-between border-t border-amber-100 py-2 text-sm">
            <div><span class="font-mono">{{ $e->command_id }}</span> — {{ $e->user->name }}
                <span class="text-xs text-gray-500">risk: {{ $e->risk }} · reason: {{ $e->reason }}</span></div>
            <div class="space-x-2">
                <form method="POST" action="{{ route('systemadmin.commands.approve', $e) }}" class="inline">@csrf
                    <button class="text-xs rounded bg-green-600 text-white px-2 py-1">Approve</button></form>
                <form method="POST" action="{{ route('systemadmin.commands.reject', $e) }}" class="inline">@csrf
                    <button class="text-xs rounded bg-red-600 text-white px-2 py-1">Reject</button></form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @foreach ($commands as $category => $items)
    <div class="rounded-lg bg-white shadow p-4 mb-4">
        <h3 class="font-semibold mb-3">{{ $category }}</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">Command</th><th class="pb-2">Risk</th><th class="pb-2">Description</th><th class="pb-2 w-56"></th></tr></thead>
            <tbody>
            @foreach ($items as $id => $c)
                <tr class="border-t" x-data="{ open: false }">
                    <td class="py-2 font-mono text-xs">{{ $id }}</td>
                    <td><span class="rounded px-1.5 py-0.5 text-xs {{ match($c['risk']) { 'low'=>'bg-green-100 text-green-700', 'medium'=>'bg-yellow-100 text-yellow-700', 'high','critical'=>'bg-red-100 text-red-700', default=>'bg-gray-100' } }}">{{ $c['risk'] }}</span></td>
                    <td class="text-xs text-gray-600">{{ $c['description'] }}</td>
                    <td class="text-right">
                        <button @click="open = !open" class="text-xs text-indigo-600 hover:underline">Run</button>
                        <div x-show="open" x-cloak class="mt-2 text-left">
                            <form method="POST" action="{{ route('systemadmin.commands.execute', $id) }}" class="space-y-1">
                                @csrf
                                @foreach (($c['parameters'] ?? []) as $pname => $prules)
                                    <input name="params[{{ $pname }}]" placeholder="{{ $pname }}" class="w-full rounded border-gray-300 text-xs">
                                @endforeach
                                @if (($c['requires_approval'] ?? false) || $c['risk'] === 'critical' || $c['risk'] === 'high')
                                    <input name="reason" placeholder="Reason (required)" class="w-full rounded border-gray-300 text-xs" required>
                                @endif
                                <button class="rounded bg-indigo-600 text-white text-xs px-3 py-1"
                                    @if(($c['risk'] ?? 'low') !== 'low') onclick="return confirm('Execute {{ $id }}? (risk: {{ $c['risk'] }})')" @endif>
                                    {{ ($c['requires_approval'] ?? false) ? 'Submit for approval' : 'Execute' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="rounded-lg bg-white shadow p-4">
        <h3 class="font-semibold mb-3">Recent Executions</h3>
        <table class="w-full text-sm"><thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">Command</th><th class="pb-2">By</th><th class="pb-2">Status</th><th class="pb-2">When</th></tr></thead>
        <tbody>@foreach ($recent as $e)<tr class="border-t"><td class="py-1.5 font-mono text-xs">{{ $e->command_id }}</td><td class="text-xs">{{ $e->user->name }}</td><td class="text-xs">{{ $e->status }}</td><td class="text-xs text-gray-400">{{ $e->created_at->diffForHumans() }}</td></tr>@endforeach</tbody></table>
        <a href="{{ route('systemadmin.commands.history') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Full history →</a>
    </div>
</x-app-layout>
