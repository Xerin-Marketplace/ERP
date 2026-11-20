<x-app-layout>
    <x-slot name="header">{{ __('Tender Opportunities') }}</x-slot>
    <div class="rounded-lg bg-white shadow p-4 mb-4">
        <form method="POST" action="{{ route('tender.store') }}" class="grid md:grid-cols-4 gap-3">
            @csrf
            <input name="reference" placeholder="Tender ref *" class="rounded border-gray-300 text-sm" required>
            <input name="buyer_name" placeholder="Buyer / institution *" class="rounded border-gray-300 text-sm" required>
            <input name="title" placeholder="Title *" class="rounded border-gray-300 text-sm" required>
            <select name="buyer_type" class="rounded border-gray-300 text-sm"><option value="public">Public</option><option value="private">Private</option><option value="international">International</option><option value="ngo">NGO</option></select>
            <select name="category" class="rounded border-gray-300 text-sm"><option value="goods">Goods</option><option value="works">Works</option><option value="services">Services</option><option value="consultancy">Consultancy</option><option value="framework">Framework</option><option value="jv">JV</option><option value="subcontract">Subcontract</option></select>
            <select name="source" class="rounded border-gray-300 text-sm"><option value="nest">NeST</option><option value="portal">Portal</option><option value="newspaper">Newspaper</option><option value="email">Email</option><option value="referral">Referral</option><option value="manual" selected>Manual</option></select>
            <input name="estimated_value" type="number" step="0.01" placeholder="Est. value" class="rounded border-gray-300 text-sm">
            <input name="submission_deadline" type="datetime-local" class="rounded border-gray-300 text-sm">
            <input name="currency" value="TZS" class="rounded border-gray-300 text-sm">
            <button class="rounded bg-indigo-600 text-white text-sm px-4 py-2">Register</button>
        </form>
        @error('reference')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="rounded-lg bg-white shadow p-4">
        <table class="w-full text-sm"><thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">Ref</th><th class="pb-2">Title</th><th class="pb-2">Buyer</th><th class="pb-2">Type</th><th class="pb-2">Stage</th><th class="pb-2">Deadline</th></tr></thead>
        <tbody>@foreach ($tenders as $t)<tr class="border-t">
            <td class="py-1.5 font-mono text-xs"><a class="text-indigo-600" href="{{ route('tender.show',$t) }}">{{ $t->reference }}</a></td>
            <td>{{ Str::limit($t->title,45) }}</td><td>{{ Str::limit($t->buyer_name,25) }}</td>
            <td class="text-xs">{{ $t->buyer_type }}</td><td><span class="text-xs rounded bg-gray-100 px-1.5">{{ $t->stage }}</span></td>
            <td class="text-xs text-gray-500">{{ $t->submission_deadline?->format('d M Y') ?? '—' }}</td></tr>
        @endforeach</tbody></table>
        <div class="mt-3">{{ $tenders->links() }}</div>
    </div>
</x-app-layout>
