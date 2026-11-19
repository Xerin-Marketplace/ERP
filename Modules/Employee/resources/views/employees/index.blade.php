<x-app-layout>
    <x-slot name="header">{{ __('Employee Directory') }}</x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, number, email"
                   class="rounded-sm border-gray-300 text-sm shadow-sm focus:border-xerin-500 focus:ring-xerin-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Company</label>
            <select name="company_id" class="rounded-sm border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}" @selected(($filters['company_id'] ?? '') == $c->id)>{{ $c->legal_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
            <select name="branch_id" class="rounded-sm border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach ($branches as $b)
                    <option value="{{ $b->id }}" @selected(($filters['branch_id'] ?? '') == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
            <select name="department_id" class="rounded-sm border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="rounded-sm border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach (['onboarding', 'active', 'suspended', 'separated'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white hover:bg-xerin-700">Filter</button>
        @can('employee.create')
            <a href="{{ route('employee.create') }}" class="rounded-sm border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">+ New Employee</a>
        @endcan
        @can('employee.export')
            <a href="{{ route('employee.export', request()->query()) }}" class="rounded-sm border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Export CSV</a>
        @endcan
    </form>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Number</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Branch / Dept</th>
                    <th class="px-4 py-3">Position</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($employees as $e)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('employee.show', $e) }}" class="font-medium text-xerin-700 hover:underline">{{ $e->full_name }}</a>
                            <p class="text-xs text-gray-400">{{ $e->official_email ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $e->employee_number }}</td>
                        <td class="px-4 py-3">{{ $e->company?->legal_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $e->branch?->name ?? '—' }}<br>{{ $e->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $e->position?->title ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $e->status === 'active' ? 'bg-green-100 text-green-700' : ($e->status === 'onboarding' ? 'bg-blue-100 text-blue-700' : ($e->status === 'suspended' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500')) }}">
                                {{ ucfirst($e->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
</x-app-layout>
