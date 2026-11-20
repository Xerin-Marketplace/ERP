<x-app-layout>
    <x-slot name="header">Employee Intelligence</x-slot>
    @include('executive._filters')
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left"><tr class="border-b text-xs text-gray-500">
                <th class="p-3">Emp #</th><th class="p-3">Name</th><th class="p-3">Position</th><th class="p-3">Department</th><th class="p-3">Branch</th>
                <th class="p-3 text-right">Open</th><th class="p-3 text-right">Done</th><th class="p-3 text-right">Overdue</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3 font-mono text-xs">{{ $row['employee']->employee_number }}</td>
                    <td class="p-3 font-semibold">{{ $row['employee']->user->name ?? '—' }}</td>
                    <td class="p-3 text-xs text-gray-500">{{ $row['employee']->position->title ?? '—' }}</td>
                    <td class="p-3 text-xs">{{ $row['employee']->department->name ?? '—' }}</td>
                    <td class="p-3 text-xs">{{ $row['employee']->branch->code ?? '—' }}</td>
                    <td class="p-3 text-right">{{ $row['tasks_open'] }}</td>
                    <td class="p-3 text-right">{{ $row['tasks_done'] }}</td>
                    <td class="p-3 text-right {{ $row['tasks_overdue'] ? 'text-red-600 font-semibold' : '' }}">{{ $row['tasks_overdue'] }}</td>
                </tr>
            @empty <tr><td colspan="8" class="p-6 text-center text-gray-400">No active employees.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
