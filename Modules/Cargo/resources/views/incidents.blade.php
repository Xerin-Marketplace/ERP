<x-app-layout>
    <x-slot name="header">Cargo Incidents</x-slot>
    <div class="space-y-4">
        <form method="GET"><select name="status" onchange="this.form.submit()" class="rounded-sm border-gray-200 text-sm"><option value="">All</option>@foreach(['open','investigating','resolved','closed'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></form>
        <div class="bg-white rounded-md border border-gray-200 divide-y divide-gray-100">
            @forelse($incidents as $i)
            <div class="px-5 py-4">
                <div class="flex justify-between"><span><span class="font-mono text-xs">{{ $i->reference }}</span> <span class="capitalize font-medium">{{ str_replace('_',' ',$i->category) }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $i->severity==='high'||$i->severity==='critical'?'bg-red-50 text-red-600':'bg-gray-100' }}">{{ $i->severity }}</span></span>
                    <span class="text-xs text-gray-500 capitalize">{{ $i->status }}</span></div>
                <div class="mt-1 text-sm text-gray-600">{{ $i->description }}@if($i->package) · <a class="text-xerin-700" href="{{ route('cargo.packages.show',$i->package) }}">{{ $i->package->reference }}</a>@endif</div>
                @if($i->status==='open' && auth()->user()->can('cargo.incidents.manage'))
                <form method="POST" action="{{ route('cargo.incidents.resolve',$i) }}" class="mt-2 flex gap-2">@csrf<input name="resolution" required placeholder="Resolution…" class="flex-1 rounded-sm border-gray-200 text-sm"><button class="px-3 py-1.5 bg-emerald-700 text-white rounded-sm text-sm">Resolve</button></form>
                @endif
            </div>
            @empty <p class="px-5 py-8 text-center text-gray-400 text-sm">No incidents.</p> @endforelse
        </div>
        {{ $incidents->links() }}
    </div>
</x-app-layout>
