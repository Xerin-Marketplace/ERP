<x-app-layout>
    <x-slot name="header">{{ __('All Employees') }}</x-slot>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        @php $cards = [
            ['Total', $stats['total'], 'gray'],
            ['Active', $stats['active'], 'green'],
            ['Onboarding', $stats['onboarding'], 'blue'],
            ['Suspended', $stats['suspended'], 'amber'],
        ]; @endphp
        @foreach ($cards as [$label, $value, $color])
            <a href="{{ route('employee.index', $label === 'Total' ? [] : ['status' => strtolower($label)]) }}"
               class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm hover:border-xerin-300 transition">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-{{ $color }}-600">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="mb-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, number, email"
                   class="rounded-sm border-gray-300 text-sm shadow-sm focus:border-xerin-500 focus:ring-xerin-500 w-56">
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
            <label class="block text-xs font-medium text-gray-500 mb-1">Position</label>
            <select name="position_id" class="rounded-sm border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach ($positions as $p)
                    <option value="{{ $p->id }}" @selected(($filters['position_id'] ?? '') == $p->id)>{{ $p->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
            <select name="employment_type" class="rounded-sm border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach (['permanent', 'contract', 'temporary', 'part_time', 'internship', 'consultancy'] as $t)
                    <option value="{{ $t }}" @selected(($filters['employment_type'] ?? '') === $t)>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
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
        @if (array_filter($filters))
            <a href="{{ route('employee.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
        <div class="ml-auto flex gap-2">
            @can('employee.create')
                <a href="{{ route('employee.create') }}" class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white hover:bg-xerin-700">+ New Employee</a>
            @endcan
            @can('employee.export')
                <a href="{{ route('employee.export', request()->query()) }}" class="rounded-sm border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Export CSV</a>
            @endcan
        </div>
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
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($employees as $e)
                    @php
                        $initials = strtoupper(mb_substr($e->personal?->first_name ?? 'E', 0, 1).mb_substr($e->personal?->last_name ?? '', 0, 1));
                        $statusColor = ['active' => 'bg-green-100 text-green-700', 'onboarding' => 'bg-blue-100 text-blue-700', 'suspended' => 'bg-amber-100 text-amber-700'][$e->status] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-xerin-100 text-xs font-bold text-xerin-700 shrink-0">{{ $initials }}</span>
                                <div>
                                    <a href="{{ route('employee.show', $e) }}" class="font-medium text-xerin-700 hover:underline">{{ $e->full_name }}</a>
                                    <p class="text-xs text-gray-400">{{ $e->official_email ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $e->employee_number }}</td>
                        <td class="px-4 py-3 text-xs">{{ $e->company?->legal_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $e->branch?->name ?? '—' }}<br><span class="text-gray-400">{{ $e->department?->name ?? '—' }}</span></td>
                        <td class="px-4 py-3 text-xs">{{ $e->position?->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ ucfirst(str_replace('_', ' ', $e->employment_type ?? '—')) }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">{{ ucfirst($e->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('employee.show', $e) }}" class="text-xs font-semibold text-xerin-700 hover:underline">View</a>
                            @can('employee.update')
                                <a href="{{ route('employee.edit', $e) }}" class="ml-2 text-xs font-semibold text-gray-600 hover:underline">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
</x-app-layout>
