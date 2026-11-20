<x-app-layout>
    <x-slot name="header">{{ __('Command Execution History') }}</x-slot>
    <div class="rounded-lg bg-white shadow p-4">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">ID</th><th class="pb-2">Command</th><th class="pb-2">User</th><th class="pb-2">Risk</th><th class="pb-2">Status</th><th class="pb-2">Duration</th><th class="pb-2">Time</th></tr></thead>
            <tbody>
            @foreach ($executions as $e)
            <tr class="border-t">
                <td class="py-1.5 text-xs">#{{ $e->id }}</td>
                <td class="font-mono text-xs">{{ $e->command_id }}</td>
                <td class="text-xs">{{ $e->user->name }}</td>
                <td class="text-xs">{{ $e->risk }}</td>
                <td><span class="text-xs {{ $e->status==='completed'?'text-green-600':($e->status==='failed'?'text-red-600':'text-gray-500') }}">{{ $e->status }}</span>
                    @if($e->error)<div class="text-xs text-red-400">{{ Str::limit($e->error, 60) }}</div>@endif</td>
                <td class="text-xs">{{ $e->duration_ms }}ms</td>
                <td class="text-xs text-gray-400">{{ $e->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div class="mt-3">{{ $executions->links() }}</div>
    </div>
</x-app-layout>
