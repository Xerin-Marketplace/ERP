<x-app-layout>
    <x-slot name="header">Department Performance</x-slot>
    @include('executive._filters')
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left"><tr class="border-b text-xs text-gray-500">
                <th class="p-3">Department</th><th class="p-3">Branch</th>
                <th class="p-3 text-right">Staff</th><th class="p-3 text-right">Open Tasks</th>
                <th class="p-3 text-right">Overdue</th><th class="p-3 text-right">Completed</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $d)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3 font-semibold">{{ $d['department']->name }}</td>
                    <td class="p-3 text-xs text-gray-500">{{ $d['department']->branch->code ?? '—' }}</td>
                    <td class="p-3 text-right">{{ $d['staff'] }}</td>
                    <td class="p-3 text-right">{{ $d['tasks_open'] }}</td>
                    <td class="p-3 text-right {{ $d['tasks_overdue'] ? 'text-red-600 font-semibold' : '' }}">{{ $d['tasks_overdue'] }}</td>
                    <td class="p-3 text-right">{{ $d['tasks_done'] }}</td>
                </tr>
            @empty <tr><td colspan="6" class="p-6 text-center text-gray-400">No departments found.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
