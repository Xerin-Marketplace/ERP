<x-app-layout>
    <x-slot name="header">{{ __('Tender Management') }}</x-slot>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold">{{ $byStage->sum() }}</div><div class="text-xs text-gray-500">Opportunities</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold">{{ $byStage->get('submitted',0) }}</div><div class="text-xs text-gray-500">Submitted</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold text-green-600">{{ $byStage->get('awarded',0)+$byStage->get('contract_active',0)+$byStage->get('execution',0) }}</div><div class="text-xs text-gray-500">Awarded/Active</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-lg font-bold">{{ number_format($awardedValue) }}</div><div class="text-xs text-gray-500">Contract Value</div></div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-3">Pipeline by Stage</h3>
            @foreach ($byStage as $stage => $count)
            <div class="flex justify-between border-t py-1.5 text-sm"><span class="capitalize">{{ str_replace('_',' ',$stage) }}</span><span class="font-semibold">{{ $count }}</span></div>
            @endforeach
        </div>
        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-3">Upcoming Deadlines</h3>
            @forelse ($upcoming as $u)
            <div class="flex justify-between border-t py-1.5 text-sm"><span>{{ Str::limit($u->title,40) }}</span><span class="text-red-600 text-xs">{{ $u->submission_deadline->format('d M Y H:i') }}</span></div>
            @empty<p class="text-sm text-gray-400">No upcoming deadlines.</p>@endforelse
            <a href="{{ route('tender.index') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">All opportunities →</a>
        </div>
    </div>
</x-app-layout>
