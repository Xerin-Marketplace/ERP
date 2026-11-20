<x-app-layout>
    <x-slot name="header">{{ __('Employee Directory') }}</x-slot>

    <div class="mb-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div><label class="block text-xs text-gray-500">Search</label>
                <input name="q" value="{{ request('q') }}" placeholder="Name or employee number" class="rounded-sm border-gray-300 text-sm w-64"></div>
            <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Search</button>
        </form>
    </div>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
                <th class="px-4 py-2">Employee</th><th class="px-4 py-2">Number</th><th class="px-4 py-2">Position</th>
                <th class="px-4 py-2">Department</th><th class="px-4 py-2">Branch</th><th class="px-4 py-2">Status</th>
            </tr></thead>
            <tbody>
                @forelse ($employees as $e)
                    <tr class="border-b last:border-0">
                        <td class="px-4 py-2 font-medium">{{ $e->full_name }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $e->employee_number }}</td>
                        <td class="px-4 py-2 text-xs">{{ $e->position?->title ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ $e->department?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ $e->branch?->name ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="text-xs rounded-full px-2 py-0.5 bg-green-100 text-green-700">{{ ucfirst($e->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $employees->links() }}</div>
</x-app-layout>
