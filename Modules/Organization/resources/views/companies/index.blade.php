<x-app-layout>
    <x-slot name="header">{{ __('Companies') }}</x-slot>

    <!-- Filters -->
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or code…"
               class="w-full sm:w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <select name="type" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All types</option>
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>
        @if (request()->hasAny(['search','type','status']))
            <a href="{{ route('organization.companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif

        @can('company.create')
            <a href="{{ route('organization.companies.create') }}" class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">+ New company</a>
        @endcan
    </form>

    <!-- Table -->
    <div class="rounded-sm border border-gray-200 bg-white overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Company</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Parent</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Region</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($companies as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-900">{{ $c->trading_name ?: $c->legal_name }}</p>
                            <p class="text-xs text-gray-500">{{ $c->trading_name ? $c->legal_name : '' }}</p>
                        </td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $c->code }}</td>
                        <td class="px-5 py-3">{{ ucfirst($c->company_type) }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $c->parent?->legal_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $c->region ?? '—' }}</td>
                        <td class="px-5 py-3">@include('organization::partials.status-badge', ['status' => $c->status])</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('organization.companies.show', $c) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">
                        No companies found. @can('company.create')<a href="{{ route('organization.companies.create') }}" class="text-indigo-600">Create the first one</a>.@endcan
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $companies->links() }}</div>
</x-app-layout>
