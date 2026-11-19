<x-app-layout>
    <x-slot name="header">{{ __('Teams') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            @forelse ($teams as $team)
                @php $wl = $workload($team); @endphp
                <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-semibold">{{ $team->name }} <span class="text-xs text-gray-400">{{ $team->code }}</span></h3>
                        <span class="text-xs text-gray-500">{{ $wl['open_tickets'] }} open tickets · {{ $wl['open_tasks'] }} open tasks</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Leader: {{ $team->leader?->full_name ?? '—' }}</p>
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach ($wl['members'] as $m)
                            <span class="text-xs rounded-full bg-gray-100 px-2 py-1">{{ $m['name'] }} <span class="text-gray-400">({{ $m['open_tickets'] }}t/{{ $m['open_tasks'] }}k)</span></span>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('ticketing.admin.teams.members', $team) }}" class="text-xs">
                        @csrf
                        <details><summary class="cursor-pointer text-xerin-700 font-semibold">Edit members</summary>
                            <div class="mt-2 flex flex-wrap gap-3">
                                @foreach ($employees as $e)
                                    <label class="flex gap-1 items-center"><input type="checkbox" name="member_ids[]" value="{{ $e->id }}" @checked($team->members->contains('id', $e->id))> {{ $e->full_name }}</label>
                                @endforeach
                            </div>
                            <button class="mt-2 rounded-sm bg-xerin-600 px-3 py-1 text-xs font-semibold text-white">Save</button>
                        </details>
                    </form>
                </div>
            @empty
                <div class="rounded-sm border bg-white p-8 text-center text-gray-400">No teams.</div>
            @endforelse
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-3">New Team</h3>
            <form method="POST" action="{{ route('ticketing.admin.teams.store') }}" class="space-y-2">
                @csrf
                <input name="code" required placeholder="Code *" class="w-full rounded-sm border-gray-300 text-sm">
                <input name="name" required placeholder="Name *" class="w-full rounded-sm border-gray-300 text-sm">
                <select name="leader_employee_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Leader</option>
                    @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Create</button>
            </form>
        </div>
    </div>
</x-app-layout>
