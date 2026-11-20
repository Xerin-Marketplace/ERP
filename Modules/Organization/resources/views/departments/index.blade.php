<x-app-layout>
    <x-slot name="header">{{ __('Departments') }}</x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or code…"
               class="w-full sm:w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <select name="company" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All companies</option>
            @foreach ($companies as $c)
                <option value="{{ $c->id }}" @selected(request('company') == $c->id)>{{ $c->legal_name }}</option>
            @endforeach
        </select>
        <select name="branch" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All branches</option>
            @foreach ($branches as $b)
                <option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>

        @can('organization.departments.create')
            <a href="{{ route('organization.departments.create') }}" class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">+ New department</a>
        @endcan
    </form>

    <div class="rounded-sm border border-gray-200 bg-white overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Department</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Company</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Parent</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($departments as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $d->name }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $d->code }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $d->company?->legal_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $d->branch?->name ?? 'Company-wide' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $d->parent?->name ?? '—' }}</td>
                        <td class="px-5 py-3">@include('organization::partials.status-badge', ['status' => $d->status])</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('organization.departments.show', $d) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">
                        No departments found. @can('organization.departments.create')<a href="{{ route('organization.departments.create') }}" class="text-indigo-600">Create the first one</a>.@endcan
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $departments->links() }}</div>
</x-app-layout>
