<x-app-layout>
    <x-slot name="header">Request {{ $request->reference }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex justify-between items-start">
                    <div><h3 class="font-semibold">{{ $request->full_name }}</h3>
                        <p class="text-xs text-gray-500">{{ $request->email }} · {{ $request->phone }}</p></div>
                    <span class="rounded-sm bg-xerin-50 px-2 py-1 text-xs font-semibold text-xerin-700">{{ str_replace('_',' ',ucfirst($request->status)) }}</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div><dt class="text-gray-400">Type</dt><dd>{{ str_replace('_',' ',$request->type) }}</dd></div>
                    <div><dt class="text-gray-400">Branch / Dept</dt><dd>{{ $request->branch->name ?? '—' }} / {{ $request->department->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-400">Job Title</dt><dd>{{ $request->job_title ?? '—' }}</dd></div>
                    <div><dt class="text-gray-400">Start / Expiry</dt><dd>{{ $request->start_date?->format('d M Y') ?? '—' }} / {{ $request->expiry_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="col-span-2"><dt class="text-gray-400">Justification</dt><dd>{{ $request->justification ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h4 class="text-sm font-semibold mb-3">Verification Checklist</h4>
                @foreach ($request->verifications as $v)
                    <div class="flex items-center justify-between border-b border-gray-50 py-2 text-xs">
                        <span>{{ ucfirst(str_replace('_',' ',$v->type)) }}</span>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold {{ $v->status === 'verified' ? 'text-green-600' : ($v->status === 'failed' ? 'text-red-600' : 'text-gray-400') }}">{{ str_replace('_',' ',$v->status) }}</span>
                            @can('reception.access.verify')
                            <form method="POST" action="{{ route('reception.access.requests.verify', [$request, $v->id]) }}">
                                @csrf <input type="hidden" name="status" value="verified">
                                <button class="text-xerin-700 font-semibold">Verify</button>
                            </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h4 class="text-sm font-semibold mb-3">Documents</h4>
                @forelse ($request->documents as $d)
                    <div class="flex items-center justify-between border-b border-gray-50 py-2 text-xs">
                        <span>{{ $d->file->original_name ?? 'file' }} <em class="text-gray-400">({{ $d->category }})</em></span>
                        <a href="{{ route('reception.access.requests.documents.download', [$request, $d->id]) }}" class="text-xerin-700 font-semibold">Download</a>
                    </div>
                @empty
                    <p class="py-2 text-xs text-gray-400">No documents attached.</p>
                @endforelse
                @can('reception.access.review')
                <form method="POST" action="{{ route('reception.access.requests.documents', $request) }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                    @csrf
                    <div class="flex gap-2">
                        <input type="file" name="documents[]" multiple required accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">
                        <select name="category" class="rounded-sm border-gray-300 text-xs">
                            <option value="supporting">Supporting</option>
                            <option value="id_document">ID document</option>
                            <option value="contract">Contract</option>
                            <option value="approval_evidence">Approval evidence</option>
                        </select>
                    </div>
                    <button class="rounded-sm bg-xerin-700 px-3 py-1.5 text-xs font-semibold text-white">Upload</button>
                </form>
                @endcan
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h4 class="text-sm font-semibold mb-3">Approval Tasks</h4>
                @forelse ($request->tasks as $t)
                    <div class="flex justify-between border-b border-gray-50 py-2 text-xs">
                        <span>Stage {{ $t->sequence }} — {{ $t->stage->name ?? '' }} <em>({{ $t->approver_role }})</em></span>
                        <span class="font-semibold">{{ $t->status }} @if($t->decided_at) · {{ $t->decided_at->format('d M') }} @endif</span>
                    </div>
                @empty <p class="text-xs text-gray-400">No approval tasks yet — route the request first.</p> @endforelse
            </div>
        </div>
        <div class="space-y-4">
            @can('reception.access.review')
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-2">
                <h4 class="text-sm font-semibold">Actions</h4>
                @if ($request->status === 'submitted')
                    <form method="POST" action="{{ route('reception.access.requests.review', $request) }}">@csrf
                        <button class="w-full rounded-sm bg-xerin-700 px-3 py-2 text-xs font-semibold text-white">Start Review</button></form>
                @endif
                @if (in_array($request->status, ['reception_review', 'verification', 'submitted']))
                    @can('reception.access.route')
                    <form method="POST" action="{{ route('reception.access.requests.route', $request) }}">@csrf
                        <button class="w-full rounded-sm bg-gray-800 px-3 py-2 text-xs font-semibold text-white">Route to Approvals</button></form>
                    @endcan
                    <form method="POST" action="{{ route('reception.access.requests.info', $request) }}">@csrf
                        <input name="note" required placeholder="Info needed…" class="w-full rounded-sm border-gray-300 text-xs mb-1">
                        <button class="w-full rounded-sm border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-700">Request Info</button></form>
                    <form method="POST" action="{{ route('reception.access.requests.reject', $request) }}">@csrf
                        <input name="reason" required placeholder="Rejection reason…" class="w-full rounded-sm border-gray-300 text-xs mb-1">
                        <button class="w-full rounded-sm border border-red-300 px-3 py-2 text-xs font-semibold text-red-700">Reject</button></form>
                @endif
            </div>
            @endcan
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h4 class="text-sm font-semibold mb-2">History</h4>
                @foreach ($request->history->sortByDesc('created_at') as $h)
                    <div class="border-l-2 border-xerin-200 pl-3 py-1.5 text-xs">
                        <p class="font-medium">{{ str_replace('.', ' — ', $h->event) }} @if($h->internal)<span class="text-gray-400">(internal)</span>@endif</p>
                        <p class="text-gray-400">{{ $h->created_at->format('d M Y H:i') }} · {{ $h->actor->name ?? 'public' }}</p>
                    </div>
                @endforeach
                @can('reception.access.view')
                <form method="POST" action="{{ route('reception.access.requests.notes', $request) }}" class="mt-3">@csrf
                    <input name="note" required placeholder="Internal note…" class="w-full rounded-sm border-gray-300 text-xs">
                    <button class="mt-1 text-xs font-semibold text-xerin-700">Add note</button></form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
