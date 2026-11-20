<x-app-layout>
    <x-slot name="header">{{ __('Locations') }}</x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search…"
               class="w-full sm:w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <select name="company" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All companies</option>
            @foreach ($companies as $c)
                <option value="{{ $c->id }}" @selected(request('company') == $c->id)>{{ $c->legal_name }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All types</option>
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ str_replace('_',' ',ucfirst($t)) }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>

        @can('organization.locations.create')
            <a href="{{ route('organization.locations.create') }}" class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">+ New location</a>
        @endcan
    </form>

    <div class="rounded-sm border border-gray-200 bg-white overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Location</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Company</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">City</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($locations as $l)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $l->name }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $l->code }}</td>
                        <td class="px-5 py-3">{{ str_replace('_',' ',ucfirst($l->location_type)) }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $l->company?->legal_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $l->branch?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $l->city ?? '—' }}</td>
                        <td class="px-5 py-3">@include('organization::partials.status-badge', ['status' => $l->status])</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">
                        No locations found. @can('organization.locations.create')<a href="{{ route('organization.locations.create') }}" class="text-indigo-600">Create the first one</a>.@endcan
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $locations->links() }}</div>
</x-app-layout>
