<x-app-layout>
    <x-slot name="header">{{ $customer->name }}</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-md border border-gray-200 p-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="font-mono text-xs text-gray-500">{{ $customer->code }}</div>
                <h2 class="text-xl font-semibold text-gray-900">{{ $customer->name }}</h2>
                <div class="mt-1 flex flex-wrap gap-3 text-sm text-gray-600">
                    <span class="capitalize">{{ $customer->type }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$customer->status) }}</span>
                    @if($customer->branch)<span>{{ $customer->branch->name }}</span>@endif
                </div>
            </div>
            @can('customer.update')<a href="{{ route('commercial.customers.edit', $customer) }}" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm">Edit</a>@endcan
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-md border border-gray-200 p-5 space-y-2 text-sm">
                <h3 class="font-semibold text-gray-900">Contact</h3>
                <div>{{ $customer->email ?: '—' }}</div><div>{{ $customer->phone ?: '—' }}</div><div>{{ $customer->whatsapp ? "WA: {$customer->whatsapp}" : '' }}</div>
                @if($customer->contact_person)<div class="text-gray-500">Contact: {{ $customer->contact_person }}</div>@endif
            </div>
            <div class="bg-white rounded-md border border-gray-200 p-5 space-y-2 text-sm">
                <h3 class="font-semibold text-gray-900">Corporate</h3>
                <div>Reg: {{ $customer->registration_no ?: '—' }}</div><div>TIN: {{ $customer->tin ?: '—' }}</div>
                <div class="text-gray-500">{{ $customer->city }}{{ $customer->city && $customer->country ? ', ' : '' }}{{ $customer->country }}</div>
            </div>
            <div class="bg-white rounded-md border border-gray-200 p-5 space-y-2 text-sm">
                <h3 class="font-semibold text-gray-900">Ownership</h3>
                <div>Manager: {{ $customer->owner?->name ?? '—' }}</div><div>Source: {{ $customer->source ?: '—' }}</div>
            </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-md border border-gray-200">
                <div class="px-5 py-3 border-b border-gray-100 font-semibold">Inquiries</div>
                <div class="divide-y divide-gray-100">
                    @forelse($customer->inquiries as $i)
                        <a href="{{ route('commercial.inquiries.show', $i) }}" class="block px-5 py-2.5 text-sm hover:bg-gray-50"><span class="font-mono text-xs">{{ $i->reference }}</span> · <span class="capitalize">{{ $i->status }}</span></a>
                    @empty <p class="px-5 py-4 text-sm text-gray-400">None yet.</p> @endforelse
                </div>
            </div>
            <div class="bg-white rounded-md border border-gray-200">
                <div class="px-5 py-3 border-b border-gray-100 font-semibold">Quotations</div>
                <div class="divide-y divide-gray-100">
                    @forelse($customer->quotations as $q)
                        <a href="{{ route('commercial.quotations.show', $q) }}" class="flex justify-between px-5 py-2.5 text-sm hover:bg-gray-50"><span class="font-mono text-xs">{{ $q->number }}</span><span>{{ number_format($q->total,2) }} {{ $q->currency }}</span></a>
                    @empty <p class="px-5 py-4 text-sm text-gray-400">None yet.</p> @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
