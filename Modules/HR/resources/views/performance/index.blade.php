<x-app-layout>
    <x-slot name="header">{{ __('Performance') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Cycles</h3>
                <ul class="text-sm space-y-1">
                    @forelse ($cycles as $c)
                        <li class="flex justify-between"><span>{{ $c->name }} <span class="text-xs text-gray-400">{{ $c->starts_at->format('M Y') }}–{{ $c->ends_at->format('M Y') }}</span></span>
                            <span class="text-xs">{{ $c->objectives_count }} objectives · {{ $c->reviews_count }} reviews</span></li>
                    @empty <li class="text-gray-400">No cycles.</li> @endforelse
                </ul>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                        <th class="px-4 py-3 text-left">Ref</th><th class="px-4 py-3 text-left">Employee</th>
                        <th class="px-4 py-3 text-left">Kind</th><th class="px-4 py-3 text-left">Rating</th><th class="px-4 py-3 text-left">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($reviews as $r)
                            <tr><td class="px-4 py-3">{{ $r->reference }}</td>
                                <td class="px-4 py-3">{{ $r->employee->full_name }}</td>
                                <td class="px-4 py-3">{{ ucfirst($r->kind) }}</td>
                                <td class="px-4 py-3">{{ $r->rating ? $r->rating.'/5' : '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ ucfirst($r->status) }}</td></tr>
                        @empty <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No reviews.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space-y-4">
            @can('performance.manage')
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">New Cycle</h3>
                <form method="POST" action="{{ route('hr.performance.cycles.store') }}" class="space-y-2">
                    @csrf
                    <input name="name" required placeholder="Name *" class="w-full rounded-sm border-gray-300 text-sm">
                    <input type="date" name="starts_at" required class="w-full rounded-sm border-gray-300 text-sm">
                    <input type="date" name="ends_at" required class="w-full rounded-sm border-gray-300 text-sm">
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Create</button>
                </form>
            </div>
            @endcan
            @if ($cycles->isNotEmpty() && ($employees->isNotEmpty() || auth()->user()->employee))
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Submit Review</h3>
                <form method="POST" action="{{ route('hr.performance.reviews.store', $cycles->first()) }}" class="space-y-2">
                    @csrf
                    @if ($employees->isNotEmpty())
                        <select name="employee_id" class="w-full rounded-sm border-gray-300 text-sm">
                            @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                        <select name="kind" class="w-full rounded-sm border-gray-300 text-sm"><option value="manager">Manager review</option><option value="peer">Peer review</option></select>
                    @else
                        <input type="hidden" name="employee_id" value="{{ auth()->user()->employee->id }}">
                        <input type="hidden" name="kind" value="self"><p class="text-xs text-gray-500">Self-assessment</p>
                    @endif
                    <select name="rating" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Rating (1–5)</option>
                        @for ($i=1;$i<=5;$i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select>
                    <textarea name="summary" placeholder="Summary" rows="3" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Submit</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
