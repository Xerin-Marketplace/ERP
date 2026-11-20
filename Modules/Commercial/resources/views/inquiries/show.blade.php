<x-app-layout>
    <x-slot name="header">Inquiry {{ $inquiry->reference }}</x-slot>
    <div class="space-y-6" x-data="{assess:false}">
        <div class="bg-white rounded-md border border-gray-200 p-5 flex flex-wrap justify-between gap-4">
            <div>
                <div class="flex items-center gap-3"><h2 class="text-lg font-semibold">{{ $inquiry->reference }}</h2><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$inquiry->status) }}</span></div>
                <div class="mt-1 text-sm text-gray-600">{{ $inquiry->customer?->name ?? $inquiry->contact_person ?? 'Walk-in / unknown customer' }} · {{ $inquiry->branch?->name ?? 'No branch' }}</div>
                <div class="mt-1 text-sm">{{ $inquiry->origin_country ?? '?' }}{{ $inquiry->origin_city ? " ({$inquiry->origin_city})" : '' }} → {{ $inquiry->destination_country ?? '?' }}{{ $inquiry->destination_city ? " ({$inquiry->destination_city})" : '' }}</div>
            </div>
            <div class="flex gap-2">
                @can('inquiry.assess')<button @click="assess=!assess" class="px-4 py-2 border border-gray-200 rounded-sm text-sm">Assess Cargo</button>@endcan
                @if($inquiry->status === 'ready_for_quote' && auth()->user()->can('quotation.create'))<a href="{{ route('commercial.quotations.create', ['inquiry' => $inquiry->id]) }}" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm">Create Quotation</a>@endif
            </div>
        </div>
        @if($missing = $inquiry->missingFields())
            <div class="bg-amber-50 border border-amber-200 rounded-md p-4 text-sm text-amber-800"><strong>Missing information:</strong> {{ implode(', ', $missing) }}</div>
        @endif
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-md border border-gray-200 p-5 text-sm space-y-2">
                <h3 class="font-semibold">Cargo Details</h3>
                <div>Category: <span class="capitalize">{{ $inquiry->cargo_category ?? '—' }}</span></div>
                <div>Packages: {{ $inquiry->package_count ?? '—' }}</div>
                <div>Est. weight: {{ $inquiry->estimated_weight ? $inquiry->estimated_weight.' kg' : '—' }}</div>
                <div class="text-gray-600">{{ $inquiry->cargo_description ?: 'No description.' }}</div>
                @if($inquiry->special_handling)<div class="text-amber-700">⚠ {{ $inquiry->special_handling }}</div>@endif
            </div>
            <div class="bg-white rounded-md border border-gray-200 p-5 text-sm space-y-2">
                <h3 class="font-semibold">Assessment</h3>
                @if($a = $inquiry->latestAssessment)
                    <div class="capitalize font-medium {{ str_contains($a->outcome,'eligible')?'text-emerald-700':'text-amber-700' }}">{{ str_replace('_',' ',$a->outcome) }}</div>
                    <div class="text-xs text-gray-500">{{ $a->assessor?->name }} · {{ $a->created_at->diffForHumans() }}</div>
                    @if($a->conditions)<div class="text-xs">{{ $a->conditions }}</div>@endif
                @else <p class="text-gray-400">Not assessed.</p> @endif
            </div>
            <div class="bg-white rounded-md border border-gray-200 p-5 text-sm space-y-2">
                <h3 class="font-semibold">Status</h3>
                @can('inquiry.update')
                <form method="POST" action="{{ route('commercial.inquiries.transition', $inquiry) }}" class="flex gap-2">@csrf
                    <select name="status" class="flex-1 rounded-sm border-gray-200 text-sm">@foreach(\Modules\Commercial\Models\Inquiry::TRANSITIONS[$inquiry->status] ?? [] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                    <button class="px-3 py-2 bg-gray-700 text-white rounded-sm text-sm">Move</button>
                </form>
                @endcan
            </div>
        </div>
        <form x-show="assess" x-cloak method="POST" action="{{ route('commercial.inquiries.assess', $inquiry) }}" class="bg-white rounded-md border border-gray-200 p-5 grid md:grid-cols-3 gap-4">
            @csrf
            @foreach(['fragile'=>'Fragile','liquid'=>'Liquid','battery'=>'Battery','perishable'=>'Perishable','dangerous_declared'=>'Dangerous goods declared'] as $f=>$l)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="{{ $f }}" value="1" class="rounded border-gray-300">{{ $l }}</label>
            @endforeach
            <div><label class="text-xs font-medium text-gray-600">Outcome *</label><select name="outcome" required class="mt-1 w-full rounded-sm border-gray-200 text-sm">@foreach(\Modules\Commercial\Models\CargoAssessment::OUTCOMES as $o)<option value="{{ $o }}">{{ ucfirst(str_replace('_',' ',$o)) }}</option>@endforeach</select></div>
            <div class="md:col-span-2"><label class="text-xs font-medium text-gray-600">Conditions / notes</label><input name="conditions" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div class="md:col-span-3"><button class="px-5 py-2 bg-xerin-700 text-white rounded-sm text-sm">Record Assessment</button></div>
        </form>
        <div class="bg-white rounded-md border border-gray-200">
            <div class="px-5 py-3 border-b border-gray-100 font-semibold">Timeline</div>
            <div class="divide-y divide-gray-100">
                @forelse($inquiry->activities as $act)
                    <div class="px-5 py-2.5 text-sm flex justify-between"><span><span class="font-medium capitalize">{{ str_replace('_',' ',$act->event) }}</span> — {{ $act->body }}</span><span class="text-xs text-gray-400">{{ $act->user?->name }} · {{ $act->created_at->diffForHumans() }}</span></div>
                @empty <p class="px-5 py-4 text-sm text-gray-400">No activity.</p> @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
