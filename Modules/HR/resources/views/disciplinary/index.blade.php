<x-app-layout>
    <x-slot name="header">{{ __('Disciplinary Cases') }} <span class="text-xs font-normal text-red-500">confidential</span></x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Ref</th><th class="px-4 py-3 text-left">Employee</th>
                    <th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-left">Severity</th><th class="px-4 py-3 text-left">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cases as $c)
                        <tr>
                            <td class="px-4 py-3"><a href="{{ route('hr.disciplinary.show', $c) }}" class="font-medium text-xerin-700 hover:underline">{{ $c->reference }}</a></td>
                            <td class="px-4 py-3">{{ $c->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ ucwords(str_replace('_',' ',$c->category)) }}</td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold {{ in_array($c->severity,['major','gross'])?'text-red-600':'text-gray-600' }}">{{ ucfirst($c->severity) }}</span></td>
                            <td class="px-4 py-3 text-xs">{{ ucfirst($c->status) }}</td>
                        </tr>
                    @empty <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No cases.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        @can('disciplinary.manage')
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-2">Register Case</h3>
            <form method="POST" action="{{ route('hr.disciplinary.store') }}" class="space-y-2">
                @csrf
                <select name="employee_id" required class="w-full rounded-sm border-gray-300 text-sm"><option value="">Employee *</option>
                    @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                <select name="category" required class="w-full rounded-sm border-gray-300 text-sm">
                    @foreach (['misconduct','insubordination','absenteeism','performance','harassment','other'] as $cat)
                        <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>@endforeach</select>
                <select name="severity" required class="w-full rounded-sm border-gray-300 text-sm">
                    <option value="minor">Minor</option><option value="moderate">Moderate</option>
                    <option value="major">Major</option><option value="gross">Gross</option></select>
                <input type="date" name="incident_date" class="w-full rounded-sm border-gray-300 text-sm">
                <textarea name="incident" required placeholder="Incident description *" rows="3" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                <button class="w-full rounded-sm bg-red-700 px-4 py-2 text-sm font-semibold text-white">Register</button>
            </form>
        </div>
        @endcan
    </div>
    <div class="mt-4">{{ $cases->links() }}</div>
</x-app-layout>
