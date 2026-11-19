<x-app-layout>
    <x-slot name="header">{{ __('Recruitment') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold border-b">Requisitions</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($requisitions as $r)
                            <tr><td class="px-4 py-3">{{ $r->reference }} — {{ $r->title }}<p class="text-xs text-gray-400">{{ $r->positions }} position(s) · {{ $r->department?->name }}</p></td>
                                <td class="px-4 py-3 text-right text-xs font-semibold {{ $r->status==='approved'?'text-green-600':($r->status==='rejected'?'text-red-600':'text-amber-600') }}">{{ ucfirst($r->status) }}
                                    @if ($r->status === 'pending' && auth()->user()->can('recruitment.approve'))
                                        <span class="flex gap-2 justify-end" x-data>
                                            <form method="POST" action="{{ route('hr.recruitment.requisitions.decide', $r) }}">@csrf
                                                <input type="hidden" name="decision" value="approved"><button>Approve</button></form>
                                            <form method="POST" action="{{ route('hr.recruitment.requisitions.decide', $r) }}">@csrf
                                                <input type="hidden" name="decision" value="rejected"><button class="text-red-600">Reject</button></form>
                                        </span>
                                    @endif</td></tr>
                        @empty <tr><td class="px-4 py-6 text-center text-gray-400">No requisitions.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold border-b">Vacancies</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($vacancies as $v)
                            <tr><td class="px-4 py-3">{{ $v->reference }} — {{ $v->title }}<p class="text-xs text-gray-400">{{ $v->applications_count }} applications · closes {{ $v->closes_at?->format('M j') ?? '—' }}</p></td>
                                <td class="px-4 py-3 text-xs">{{ ucfirst($v->status) }}</td></tr>
                        @empty <tr><td class="px-4 py-6 text-center text-gray-400">No vacancies.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold border-b">Applications</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($applications as $a)
                            <tr><td class="px-4 py-3">{{ $a->full_name }}<p class="text-xs text-gray-400">{{ $a->vacancy->title }} · {{ $a->reference }}</p></td>
                                <td class="px-4 py-3">
                                    @can('recruitment.manage')
                                    <form method="POST" action="{{ route('hr.recruitment.applications.stage', $a) }}" class="flex gap-1 items-center">@csrf
                                        <select name="stage" onchange="this.form.submit()" class="text-xs rounded-sm border-gray-200 py-0.5">
                                            @foreach (\Modules\HR\Services\RecruitmentService::STAGES as $s)
                                                <option value="{{ $s }}" @selected($a->stage === $s)>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select></form>
                                    @else {{ ucfirst($a->stage) }} @endcan
                                </td></tr>
                        @empty <tr><td class="px-4 py-6 text-center text-gray-400">No applications.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @can('recruitment.manage')
        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">New Requisition</h3>
                <form method="POST" action="{{ route('hr.recruitment.requisitions.store') }}" class="space-y-2">
                    @csrf
                    <input name="title" required placeholder="Position title *" class="w-full rounded-sm border-gray-300 text-sm">
                    <select name="company_id" required class="w-full rounded-sm border-gray-300 text-sm"><option value="">Company *</option>
                        @foreach ($companies as $c)<option value="{{ $c->id }}">{{ $c->legal_name }}</option>@endforeach</select>
                    <select name="department_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Department</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                    <input name="positions" type="number" value="1" min="1" class="w-full rounded-sm border-gray-300 text-sm">
                    <textarea name="justification" placeholder="Justification" rows="2" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Submit</button>
                </form>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Publish Vacancy</h3>
                <form method="POST" action="{{ route('hr.recruitment.vacancies.store') }}" class="space-y-2">
                    @csrf
                    <input name="title" required placeholder="Title *" class="w-full rounded-sm border-gray-300 text-sm">
                    <textarea name="description" required placeholder="Description *" rows="3" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                    <input type="date" name="closes_at" class="w-full rounded-sm border-gray-300 text-sm" title="Closing date">
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Publish</button>
                </form>
            </div>
        </div>
        @endcan
    </div>
</x-app-layout>
