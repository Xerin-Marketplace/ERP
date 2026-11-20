<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>
    @can('hr.payroll.process')
        <div class="mb-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <form method="POST" action="{{ route('hr.payroll.periods.store') }}" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div><label class="block text-xs text-gray-500">Period</label>
                    <input name="name" required placeholder="2026-02" class="rounded-sm border-gray-300 text-sm"></div>
                <div><label class="block text-xs text-gray-500">Start</label>
                    <input type="date" name="start_date" required class="rounded-sm border-gray-300 text-sm"></div>
                <div><label class="block text-xs text-gray-500">End</label>
                    <input type="date" name="end_date" required class="rounded-sm border-gray-300 text-sm"></div>
                <div><label class="block text-xs text-gray-500">Branch (blank = all)</label>
                    <select name="branch_id" class="rounded-sm border-gray-300 text-sm"><option value="">All branches</option>
                        @foreach (\Modules\Organization\Models\Branch::orderBy('code')->get() as $b)
                            <option value="{{ $b->id }}">{{ $b->code }} — {{ $b->name }}</option>
                        @endforeach</select></div>
                <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Open Period</button>
            </form>
        </div>
    @endcan
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Periods</h3>
            @forelse ($periods as $p)
                <div class="py-2 border-b last:border-0 text-sm">
                    <div class="flex justify-between items-center">
                        <a href="{{ route('hr.payroll.index', ['period_id' => $p->id]) }}" class="font-medium text-xerin-700">{{ $p->name }}</a>
                        <span class="text-xs rounded-full px-2 py-0.5 {{ $p->status==='approved' ? 'bg-green-100 text-green-700' : ($p->status==='processing' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">{{ ucfirst($p->status) }}</span>
                    </div>
                    <p class="text-xs text-gray-400">{{ $p->runs_count }} runs · {{ $p->branch?->code ?? 'All branches' }}</p>
                    <div class="flex gap-2 mt-1">
                        @can('hr.payroll.process')
                            @if (in_array($p->status, ['open','processing']))
                                <form method="POST" action="{{ route('hr.payroll.process', $p) }}">@csrf
                                    <button class="text-xs font-semibold text-xerin-700">Process</button></form>
                            @endif
                        @endcan
                        @can('hr.payroll.approve')
                            @if ($p->status === 'processing')
                                <form method="POST" action="{{ route('hr.payroll.approve', $p) }}">@csrf
                                    <button class="text-xs font-semibold text-green-700">Approve all</button></form>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty <p class="text-sm text-gray-400">No periods.</p> @endforelse
            <div class="mt-3">{{ $periods->links() }}</div>
        </div>
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Runs{{ request('period_id') ? ' — period filter applied' : '' }}</h3>
            @forelse ($runs as $r)
                <div class="flex items-center justify-between py-2 border-b last:border-0 text-sm">
                    <div>
                        <p class="font-medium">{{ $r->reference }} — {{ $r->employee->personal->first_name ?? '' }} {{ $r->employee->personal->last_name ?? $r->employee->employee_number }}</p>
                        <p class="text-xs text-gray-500">{{ $r->currency }} {{ number_format($r->gross_amount,2) }} gross · {{ number_format($r->net_amount,2) }} net</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs rounded-full px-2 py-0.5 {{ $r->status==='paid' ? 'bg-green-100 text-green-700' : ($r->status==='approved' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500') }}">{{ ucfirst($r->status) }}</span>
                        @can('hr.payroll.approve')
                            @if ($r->status === 'approved')
                                <form method="POST" action="{{ route('hr.payroll.pay', $r) }}">@csrf
                                    <button class="text-xs font-semibold text-green-700">Mark paid</button></form>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty <p class="text-sm text-gray-400">No runs yet — process a period.</p> @endforelse
        </div>
    </div>
</x-app-layout>
