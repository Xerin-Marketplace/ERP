<x-app-layout>
    <x-slot name="header">{{ __('Employee Welfare') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Ref</th><th class="px-4 py-3 text-left">Employee</th>
                    <th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Title</th><th class="px-4 py-3 text-left">Date</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $w)
                        <tr><td class="px-4 py-3">{{ $w->reference }}</td>
                            <td class="px-4 py-3">{{ $w->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ ucfirst($w->type) }}</td>
                            <td class="px-4 py-3">{{ $w->title }}</td>
                            <td class="px-4 py-3">{{ $w->recorded_on->format('M j, Y') }}</td></tr>
                    @empty <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No records.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        @can('welfare.manage')
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-2">New Record</h3>
            <form method="POST" action="{{ route('hr.welfare.store') }}" class="space-y-2">
                @csrf
                <select name="employee_id" required class="w-full rounded-sm border-gray-300 text-sm"><option value="">Employee *</option>
                    @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                <select name="type" required class="w-full rounded-sm border-gray-300 text-sm">
                    <option value="benefit">Benefit</option><option value="assistance">Assistance</option>
                    <option value="program">Program</option><option value="activity">Activity</option></select>
                <input name="title" required placeholder="Title *" class="w-full rounded-sm border-gray-300 text-sm">
                <input type="date" name="recorded_on" required class="w-full rounded-sm border-gray-300 text-sm">
                <textarea name="detail" placeholder="Detail" rows="2" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
            </form>
        </div>
        @endcan
    </div>
</x-app-layout>
