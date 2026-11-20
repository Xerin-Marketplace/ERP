<x-app-layout>
    <x-slot name="header">Customers</x-slot>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" class="flex gap-2 flex-1 min-w-60">
                <input name="q" value="{{ request('q') }}" placeholder="Search name, code, email, phone…" class="flex-1 rounded-sm border-gray-200 text-sm">
                <select name="type" class="rounded-sm border-gray-200 text-sm"><option value="">All types</option>@foreach(['individual','business','agent','wholesale'] as $t)<option value="{{ $t }}" @selected(request('type')===$t)>{{ ucfirst($t) }}</option>@endforeach</select>
                <select name="status" class="rounded-sm border-gray-200 text-sm"><option value="">All statuses</option>@foreach(['prospect','active','inactive','on_hold','restricted','archived'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                <button class="px-4 py-2 bg-xerin-600 text-white rounded-sm text-sm">Filter</button>
            </form>
            @can('customer.create')<a href="{{ route('commercial.customers.create') }}" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">+ New Customer</a>@endcan
        </div>
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Reference</th><th class="px-3 py-2.5 text-left">Name</th><th class="px-3 py-2.5 text-left">Type</th><th class="px-3 py-2.5 text-left">Contact</th><th class="px-3 py-2.5 text-left">Branch</th><th class="px-3 py-2.5 text-left">Status</th><th class="px-5 py-2.5"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $c)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs">{{ $c->code }}</td>
                            <td class="px-3 py-3 font-medium text-gray-900">{{ $c->name }}@if($c->trading_name)<div class="text-xs text-gray-500">{{ $c->trading_name }}</div>@endif</td>
                            <td class="px-3 py-3 capitalize">{{ $c->type }}</td>
                            <td class="px-3 py-3 text-xs text-gray-600">{{ $c->email }}<br>{{ $c->phone }}</td>
                            <td class="px-3 py-3">{{ $c->branch?->name ?? '—' }}</td>
                            <td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs capitalize {{ $c->status==='active'?'bg-emerald-50 text-emerald-700':'bg-gray-100 text-gray-600' }}">{{ str_replace('_',' ',$c->status) }}</span></td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('commercial.customers.show', $c) }}" class="text-xerin-700 text-sm hover:underline">View</a></td>
                        </tr>
                    @empty <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">No customers found.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        {{ $customers->links() }}
    </div>
</x-app-layout>
