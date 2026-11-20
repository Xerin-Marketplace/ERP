<x-app-layout>
    <x-slot name="header">Claim {{ $c->reference }}</x-slot>
    <div class="grid md:grid-cols-2 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5 space-y-3 text-sm">
            <div class="flex justify-between"><span>Status</span><span class="px-2 rounded bg-amber-100 text-amber-700">{{ str_replace('_',' ',$c->status) }}</span></div>
            <div class="flex justify-between"><span>Customer</span><span>{{ $c->customer?->name }}</span></div>
            <div class="flex justify-between"><span>Type</span><span class="capitalize">{{ str_replace('_',' ',$c->type) }}</span></div>
            <div class="flex justify-between"><span>Claimed</span><span>{{ number_format($c->claimed_amount) }} {{ $c->currency }}</span></div>
            <div class="flex justify-between"><span>Approved</span><span>{{ $c->approved_amount ? number_format($c->approved_amount) : '—' }}</span></div>
            <div class="flex justify-between"><span>Investigator</span><span>{{ $c->investigator?->name ?? '—' }}</span></div>
            <div class="flex justify-between"><span>Decided by</span><span>{{ $c->decider?->name ?? '—' }}</span></div>
            <p class="text-gray-600 border-t pt-2">{{ $c->description }}</p>
            @if($c->investigation_notes)<p class="text-xs text-gray-500">Investigation: {{ $c->investigation_notes }}</p>@endif
        </div>
        <div class="space-y-5">
            @if($c->status === 'investigation' || $c->status === 'under_review')
            <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-2">Complete Investigation</h3>
                <form method="POST" action="{{ route('desk.claims.investigate', $c) }}" class="space-y-2">@csrf
                    <textarea name="notes" required placeholder="Findings + recommendation" class="w-full rounded border-gray-300 text-sm" rows="3"></textarea>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Submit for Approval</button></form></div>
            @endif
            @if($c->status === 'pending_approval')
            <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-2">Decision</h3>
                <form method="POST" action="{{ route('desk.claims.decide', $c) }}" class="space-y-2">@csrf
                    <select name="decision" class="w-full rounded border-gray-300 text-sm"><option value="approved">Approve full</option><option value="partially_approved">Partial approve</option><option value="rejected">Reject</option></select>
                    <input name="amount" placeholder="Approved amount (if partial)" class="w-full rounded border-gray-300 text-sm">
                    <textarea name="reason" required placeholder="Decision reason" class="w-full rounded border-gray-300 text-sm" rows="2"></textarea>
                    <button class="px-4 py-2 bg-green-600 text-white rounded text-sm">Record Decision</button></form></div>
            @endif
            @if(in_array($c->status, ['approved','partially_approved','settlement_pending']))
            <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-2">Settlement</h3>
                <form method="POST" action="{{ route('desk.claims.settle', $c) }}" class="flex gap-2">@csrf
                    <input name="settlement_ref" placeholder="Finance payment/credit ref" class="rounded border-gray-300 text-sm flex-1">
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">{{ $c->status === 'settlement_pending' ? 'Mark Settled' : 'Request Settlement' }}</button></form></div>
            @endif
            <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-2">Timeline</h3>
                @foreach($c->events as $e)<div class="text-xs flex gap-2"><span class="text-gray-400">{{ $e->created_at->format('d M H:i') }}</span><span>{{ $e->event }}</span><span class="text-gray-400">{{ $e->user?->name }}</span></div>@endforeach
            </div>
        </div>
    </div>
</x-app-layout>
