<x-app-layout>
    <x-slot name="header">{{ __('Positions & Grades') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Code</th><th class="px-4 py-3 text-left">Title</th>
                    <th class="px-4 py-3 text-left">Company / Dept</th><th class="px-4 py-3 text-left">Grade</th>
                    <th class="px-4 py-3 text-left">Reports To</th><th class="px-4 py-3 text-left">Staff</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($positions as $p)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $p->code }}</td>
                            <td class="px-4 py-3 font-medium">{{ $p->title }}<p class="text-xs text-gray-400">{{ ucfirst($p->status) }}</p></td>
                            <td class="px-4 py-3 text-xs">{{ $p->company?->legal_name }}<br>{{ $p->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $p->grade?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $p->reportsTo?->title ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $p->employees_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No positions defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $positions->links() }}</div>
        </div>

        <div class="space-y-4">
            @can('employee.position.manage')
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">New Position</h3>
                <form method="POST" action="{{ route('employee.positions.store') }}" class="space-y-2 text-sm">
                    @csrf
                    <input name="code" placeholder="Code *" required class="w-full rounded-sm border-gray-300 text-sm">
                    <input name="title" placeholder="Title *" required class="w-full rounded-sm border-gray-300 text-sm">
                    <select name="company_id" required class="w-full rounded-sm border-gray-300 text-sm">
                        <option value="">Company *</option>
                        @foreach ($companies as $c)<option value="{{ $c->id }}">{{ $c->legal_name }}</option>@endforeach
                    </select>
                    <select name="department_id" class="w-full rounded-sm border-gray-300 text-sm">
                        <option value="">Department</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                    <select name="job_grade_id" class="w-full rounded-sm border-gray-300 text-sm">
                        <option value="">Grade</option>
                        @foreach ($grades as $g)<option value="{{ $g->id }}">{{ $g->code }} — {{ $g->name }}</option>@endforeach
                    </select>
                    <select name="reports_to_position_id" class="w-full rounded-sm border-gray-300 text-sm">
                        <option value="">Reports to</option>
                        @foreach ($allPositions as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
                    </select>
                    <input name="category" placeholder="Category" class="w-full rounded-sm border-gray-300 text-sm">
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Add Position</button>
                </form>
            </div>
            @endcan

            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Job Grades</h3>
                <ul class="text-sm divide-y divide-gray-100 mb-3">
                    @foreach ($grades as $g)
                        <li class="py-1.5 flex justify-between"><span>{{ $g->code }} — {{ $g->name }}</span><span class="text-xs text-gray-400">L{{ $g->level }}</span></li>
                    @endforeach
                </ul>
                @can('employee.grade.manage')
                <form method="POST" action="{{ route('employee.grades.store') }}" class="space-y-2">
                    @csrf
                    <div class="flex gap-2">
                        <input name="code" placeholder="Code" required class="w-20 rounded-sm border-gray-300 text-sm">
                        <input name="name" placeholder="Name" required class="flex-1 rounded-sm border-gray-300 text-sm">
                        <input name="level" type="number" placeholder="Lvl" required min="1" class="w-16 rounded-sm border-gray-300 text-sm">
                    </div>
                    <button class="w-full rounded-sm border border-xerin-600 px-4 py-1.5 text-sm font-semibold text-xerin-700">Add Grade</button>
                </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
