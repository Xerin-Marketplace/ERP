<x-app-layout>
    <x-slot name="header">Inquiries</x-slot>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" class="flex gap-2 flex-1 min-w-60">
                <input name="q" value="{{ request('q') }}" placeholder="Search reference, description…" class="flex-1 rounded-sm border-gray-200 text-sm">
                <select name="status" class="rounded-sm border-gray-200 text-sm"><option value="">All statuses</option>@foreach(\Modules\Commercial\Models\Inquiry::STATUSES as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                <button class="px-4 py-2 bg-xerin-600 text-white rounded-sm text-sm">Filter</button>
            </form>
            @can('inquiry.create')<a href="{{ route('commercial.inquiries.create') }}" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">+ New Inquiry</a>@endcan
        </div>
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Ref</th><th class="px-3 py-2.5 text-left">Customer</th><th class="px-3 py-2.5 text-left">Route</th><th class="px-3 py-2.5 text-left">Cargo</th><th class="px-3 py-2.5 text-left">Status</th><th class="px-3 py-2.5 text-left">Agent</th><th class="px-5 py-2.5"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($inquiries as $i)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs">{{ $i->reference }}</td>
                            <td class="px-3 py-3">{{ $i->customer?->name ?? $i->contact_person ?? '—' }}</td>
                            <td class="px-3 py-3 text-xs">{{ $i->origin_country ?? '?' }} → {{ $i->destination_country ?? '?' }}</td>
                            <td class="px-3 py-3 text-xs capitalize">{{ $i->cargo_category ?? '—' }}</td>
                            <td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$i->status) }}</span></td>
                            <td class="px-3 py-3 text-xs">{{ $i->agent?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('commercial.inquiries.show', $i) }}" class="text-xerin-700 text-sm hover:underline">View</a></td>
                        </tr>
                    @empty <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">No inquiries found.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        {{ $inquiries->links() }}
    </div>
</x-app-layout>
