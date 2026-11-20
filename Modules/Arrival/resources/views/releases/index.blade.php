<x-app-layout>
    <x-slot name="header">Release Approvals</x-slot>
    <p class="mb-4 text-sm text-gray-500">{{ $pendingCount }} pending — no cargo leaves without an approved release code (spec 14.4).</p>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Ref</th><th class="px-4 py-2">Package</th><th class="px-4 py-2">Invoice</th>
                <th class="px-4 py-2">Status</th><th class="px-4 py-2">Code</th><th class="px-4 py-2">Decided by</th><th class="px-4 py-2">Actions</th></tr></thead>
            <tbody>
            @forelse ($approvals as $a)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $a->reference }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $a->package->reference ?? $a->package_id }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $a->invoice->reference ?? '—' }}</td>
                    <td class="px-4 py-2"><span class="rounded-sm px-2 py-0.5 text-xs font-semibold
                        bg-{{ ['approved'=>'green','rejected'=>'red','on_hold'=>'amber','released'=>'gray'][$a->status] ?? 'amber' }}-50">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span></td>
                    <td class="px-4 py-2 font-mono text-sm font-bold">{{ $a->release_code ?? '—' }}</td>
                    <td class="px-4 py-2 text-xs">{{ $a->decider->name ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @if ($a->status === 'pending')
                        @can('release.approve')
                        <form method="POST" action="{{ route('arrival.releases.decide', $a) }}" class="flex gap-1 items-center">
                            @csrf
                            <input name="reason" placeholder="Reason" class="w-24 rounded-sm border-gray-300 text-xs">
                            <button name="decision" value="approved" class="rounded-sm bg-green-700 px-2 py-1 text-xs font-semibold text-white">Approve</button>
                            <button name="decision" value="on_hold" class="rounded-sm bg-amber-600 px-2 py-1 text-xs font-semibold text-white">Hold</button>
                            <button name="decision" value="rejected" class="rounded-sm bg-red-700 px-2 py-1 text-xs font-semibold text-white">Reject</button>
                        </form>
                        @endcan
                        @endif
                    </td>
                </tr>
            @empty <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No release approvals.</td></tr> @endforelse
            </tbody></table>
        <div class="p-4">{{ $approvals->links() }}</div>
    </div>
    @can('release.view')
    <div class="mt-4 max-w-md rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
        <h4 class="text-sm font-semibold">Verify Release Code at hand-over</h4>
        <form method="POST" action="{{ route('arrival.releases.verify') }}" class="mt-3 flex gap-2">
            @csrf
            <input name="package_id" type="number" required placeholder="Package ID" class="w-32 rounded-sm border-gray-300 text-sm">
            <input name="code" maxlength="6" required placeholder="6-digit code" class="w-32 rounded-sm border-gray-300 text-sm font-mono">
            <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Verify</button>
        </form>
    </div>
    @endcan
</x-app-layout>
