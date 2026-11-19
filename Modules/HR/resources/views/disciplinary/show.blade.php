<x-app-layout>
    <x-slot name="header">{{ $case->reference }} <span class="text-xs font-normal text-red-500">confidential · {{ ucfirst($case->status) }}</span></x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <dl class="text-sm space-y-1.5">
                    <div class="flex justify-between"><dt class="text-gray-500">Employee</dt><dd>{{ $case->employee->full_name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Category / Severity</dt><dd>{{ ucwords(str_replace('_',' ',$case->category)) }} · {{ ucfirst($case->severity) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Incident date</dt><dd>{{ $case->incident_date?->format('M j, Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Investigator</dt><dd>{{ $case->investigator?->name ?? '—' }}</dd></div>
                </dl>
                <p class="mt-3 text-sm whitespace-pre-wrap border-t pt-3">{{ $case->incident }}</p>
                @if ($case->employee_response)
                    <p class="mt-3 text-sm whitespace-pre-wrap border-t pt-3"><b>Employee response:</b> {{ $case->employee_response }}</p>
                @endif
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Action History</h3>
                <ul class="text-sm space-y-1.5">
                    @foreach ($case->actions as $a)
                        <li class="border-l-2 border-gray-200 pl-3"><b>{{ ucwords(str_replace('_',' ',$a->type)) }}</b> — {{ $a->detail }}
                            <span class="text-xs text-gray-400">· {{ $a->actor->name }} · {{ $a->created_at->diffForHumans() }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
        @can('disciplinary.manage')
        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Record Action</h3>
                <form method="POST" action="{{ route('hr.disciplinary.action', $case) }}" class="space-y-2">
                    @csrf
                    <select name="type" required class="w-full rounded-sm border-gray-300 text-sm">
                        @foreach (['verbal_warning','written_warning','final_warning','suspension','termination','exonerated','appeal','note'] as $ty)
                            <option value="{{ $ty }}">{{ ucwords(str_replace('_',' ',$ty)) }}</option>@endforeach</select>
                    <textarea name="detail" placeholder="Detail" rows="2" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Record</button>
                </form>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Advance Status</h3>
                <form method="POST" action="{{ route('hr.disciplinary.status', $case) }}">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="w-full rounded-sm border-gray-300 text-sm">
                        @foreach (\Modules\HR\Services\DisciplinaryService::STATUSES as $s)
                            <option value="{{ $s }}" @selected($case->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                </form>
            </div>
            @if (! $case->employee_response)
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Employee Response</h3>
                <form method="POST" action="{{ route('hr.disciplinary.response', $case) }}">@csrf
                    <textarea name="employee_response" required rows="3" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                    <button class="mt-2 w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Save</button></form>
            </div>
            @endif
        </div>
        @endcan
    </div>
</x-app-layout>
