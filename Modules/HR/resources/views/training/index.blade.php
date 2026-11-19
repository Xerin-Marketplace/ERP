<x-app-layout>
    <x-slot name="header">{{ __('Training') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                        <th class="px-4 py-3 text-left">Program</th><th class="px-4 py-3 text-left">Dates</th>
                        <th class="px-4 py-3 text-left">Enrolled</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($programs as $p)
                            <tr><td class="px-4 py-3 font-medium">{{ $p->title }} <span class="text-xs text-gray-400">{{ $p->code }}</span>
                                    @if ($p->certifies)<span class="text-xs text-indigo-600">certifies</span>@endif</td>
                                <td class="px-4 py-3 text-xs">{{ $p->starts_at?->format('M j') ?? '—' }}–{{ $p->ends_at?->format('M j') ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $p->enrollments_count }}{{ $p->capacity ? '/'.$p->capacity : '' }}</td>
                                <td class="px-4 py-3 text-xs">{{ ucfirst($p->status) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($p->status === 'open')
                                        @can('training.manage')
                                        <form method="POST" action="{{ route('hr.training.enroll', $p) }}" class="flex gap-1 justify-end">@csrf
                                            <select name="employee_id" class="text-xs rounded-sm border-gray-200 py-0.5 max-w-32">
                                                @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                                            <button class="text-xs font-semibold text-xerin-700">Enroll</button></form>
                                        @elsecan('training.view')
                                        <form method="POST" action="{{ route('hr.training.enroll', $p) }}">@csrf
                                            <input type="hidden" name="employee_id" value="{{ auth()->user()->employee?->id }}">
                                            <button class="text-xs font-semibold text-xerin-700">Enroll me</button></form>
                                        @endcan
                                    @endif
                                </td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($myEnrollments->isNotEmpty())
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">My Training</h3>
                <ul class="text-sm space-y-1">
                    @foreach ($myEnrollments as $e)
                        <li class="flex justify-between"><span>{{ $e->program->title }}</span><span class="text-xs">{{ ucfirst($e->status) }}{{ $e->score !== null ? " · {$e->score}%" : '' }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @can('training.manage')
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-2">New Program</h3>
            <form method="POST" action="{{ route('hr.training.programs.store') }}" class="space-y-2">
                @csrf
                <input name="code" required placeholder="Code *" class="w-full rounded-sm border-gray-300 text-sm">
                <input name="title" required placeholder="Title *" class="w-full rounded-sm border-gray-300 text-sm">
                <input name="provider" placeholder="Provider" class="w-full rounded-sm border-gray-300 text-sm">
                <div class="flex gap-2"><input type="date" name="starts_at" class="w-full rounded-sm border-gray-300 text-sm"><input type="date" name="ends_at" class="w-full rounded-sm border-gray-300 text-sm"></div>
                <input name="capacity" type="number" placeholder="Capacity" class="w-full rounded-sm border-gray-300 text-sm">
                <label class="text-xs flex gap-1 items-center"><input type="checkbox" name="certifies" value="1"> Issues certificate</label>
                <input name="cert_validity_months" type="number" placeholder="Cert validity (months)" class="w-full rounded-sm border-gray-300 text-sm">
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Create</button>
            </form>
        </div>
        @endcan
    </div>
</x-app-layout>
