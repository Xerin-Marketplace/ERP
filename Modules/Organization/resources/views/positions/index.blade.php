<x-app-layout>
    <x-slot name="header">Positions</x-slot>

    <div class="rounded-sm border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <form method="GET" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or code…"
                       class="w-64 rounded-sm border-gray-300 text-sm">
                <select name="company" class="rounded-sm border-gray-300 text-sm">
                    <option value="">All companies</option>
                    @foreach ($companies as $c)
                        <option value="{{ $c->id }}" @selected(request('company') == $c->id)>{{ $c->legal_name }}</option>
                    @endforeach
                </select>
                <button class="rounded-sm border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600">Filter</button>
            </form>
            @can('organization.positions.create')
                <a href="{{ route('organization.positions.create') }}" class="rounded-sm bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">+ New position</a>
            @endcan
        </div>

        <table class="w-full text-sm">
            <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-400">
                <tr>
                    <th class="px-5 py-3">Code</th><th class="px-5 py-3">Title</th>
                    <th class="px-5 py-3">Company</th><th class="px-5 py-3">Department</th>
                    <th class="px-5 py-3">Status</th><th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($positions as $p)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs">{{ $p->code }}</td>
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $p->title }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $p->company?->legal_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $p->department?->name ?? '—' }}</td>
                        <td class="px-5 py-3"><span class="text-xs {{ $p->status === 'active' ? 'text-emerald-600' : 'text-gray-400' }}">{{ ucfirst($p->status) }}</span></td>
                        <td class="px-5 py-3 text-right">
                            @can('organization.positions.update')
                                <a href="{{ route('organization.positions.edit', $p) }}" class="text-xs font-medium text-indigo-600">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No positions found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 px-5 py-3">{{ $positions->links() }}</div>
    </div>
</x-app-layout>
