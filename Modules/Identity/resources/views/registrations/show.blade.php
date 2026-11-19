<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $application->reference }}</span>
            @php $cls = match($application->status->value) {
                'submitted','pending_review' => 'bg-amber-100 text-amber-700',
                'under_review' => 'bg-blue-100 text-blue-700',
                'information_requested' => 'bg-purple-100 text-purple-700',
                'approved','provisioning' => 'bg-green-100 text-green-700',
                'rejected','cancelled' => 'bg-red-100 text-red-700',
                default => 'bg-gray-200 text-gray-700',
            }; @endphp
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $cls }}">{{ $application->status->publicLabel() }}</span>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Left: details + docs + timeline --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Applicant</h2></div>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 px-5 py-4 text-sm">
                    <div><dt class="text-xs text-gray-500">Name</dt><dd class="font-medium">{{ $application->full_name }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Email</dt><dd>{{ $application->email }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Phone</dt><dd>{{ $application->phone }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Alt phone</dt><dd>{{ $application->alt_phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Date of birth</dt><dd>{{ $application->date_of_birth?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Address</dt><dd>{{ $application->residential_address ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Employee no.</dt><dd>{{ $application->employee_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Employment type</dt><dd>{{ $application->employment_type ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Position requested</dt><dd>{{ $application->job_title ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Start date</dt><dd>{{ $application->employment_start_date?->format('d M Y') ?? '—' }}</dd></div>
                </dl>
                @if ($application->registration_reason)
                    <div class="px-5 pb-4">
                        <dt class="text-xs text-gray-500">Reason</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ $application->registration_reason }}</dd>
                    </div>
                @endif
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Requested placement <span class="text-xs font-normal text-gray-400">(applicant request — verify before assigning)</span></h2></div>
                <dl class="grid grid-cols-3 gap-x-6 gap-y-3 px-5 py-4 text-sm">
                    <div><dt class="text-xs text-gray-500">Company</dt><dd>{{ $application->requestedCompany?->legal_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Branch</dt><dd>{{ $application->requestedBranch?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Department</dt><dd>{{ $application->requestedDepartment?->name ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Documents</h2></div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($application->documents as $doc)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $doc->label ?? $doc->file->original_name }}</p>
                                <p class="text-xs text-gray-400">{{ str_replace('_', ' ', $doc->document_type) }} · {{ number_format($doc->file->size / 1024, 0) }} KB</p>
                            </div>
                            <a href="{{ route('identity.documents.download', $doc) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Download</a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-gray-400">No documents uploaded.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Approval timeline</h2></div>
                <ol class="relative border-l border-gray-200 m-5 space-y-5">
                    @foreach ($application->statusHistories as $h)
                        <li class="ml-5">
                            <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-indigo-500 ring-4 ring-indigo-100"></span>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $h->from_status ? \Modules\Identity\Enums\ApplicationStatus::from($h->from_status)->publicLabel().' → ' : '' }}
                                {{ \Modules\Identity\Enums\ApplicationStatus::from($h->to_status)->publicLabel() }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $h->actor?->name ?? 'System' }} · {{ $h->created_at->format('d M Y, H:i') }}</p>
                            @if ($h->note)<p class="mt-0.5 text-xs text-gray-500">{{ $h->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Internal notes <span class="text-xs font-normal text-red-500">(never visible to applicant)</span></h2></div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($application->reviews as $r)
                        <li class="px-5 py-3">
                            <p class="text-sm text-gray-700">{{ $r->note }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ $r->reviewer->name }} · {{ $r->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-gray-400">No internal notes yet.</li>
                    @endforelse
                </ul>
                @can('registration.review')
                    <form method="POST" action="{{ route('identity.registrations.note', $application) }}" class="px-5 py-4 border-t border-gray-100 flex gap-2">
                        @csrf
                        <input name="note" required maxlength="2000" placeholder="Add an internal note…"
                               class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Add</button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Right: actions --}}
        <div class="space-y-4">
            @can('registration.review')
                <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                    <h2 class="font-semibold text-gray-900">Actions</h2>

                    @if (in_array($application->status->value, ['submitted', 'pending_review']))
                        <form method="POST" action="{{ route('identity.registrations.review', $application) }}">
                            @csrf
                            <button class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">Start review</button>
                        </form>
                    @endif

                    @if (in_array($application->status->value, ['under_review', 'information_requested']))
                        <form method="POST" action="{{ route('identity.registrations.request-info', $application) }}" class="space-y-2">
                            @csrf
                            <textarea name="message" rows="2" required placeholder="Message to applicant…" class="w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea>
                            <button class="w-full rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-500">Request information</button>
                        </form>

                        @can('registration.approve')
                            <form method="POST" action="{{ route('identity.registrations.approve', $application) }}"
                                  onsubmit="return confirm('Approve this application?')">
                                @csrf
                                <button class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-500">Approve</button>
                            </form>
                        @endcan

                        @can('registration.reject')
                            <form method="POST" action="{{ route('identity.registrations.reject', $application) }}" class="space-y-2">
                                @csrf
                                <textarea name="reason" rows="2" required placeholder="Rejection reason (required)…" class="w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea>
                                <button class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500"
                                        onclick="return confirm('Reject this application?')">Reject</button>
                            </form>
                        @endcan
                    @endif
                </div>
            @endcan

            @if ($application->status === \Modules\Identity\Enums\ApplicationStatus::Approved)
                @can('registration.provision')
                    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm"
                         x-data="{
                            company: '{{ $application->requested_company_id }}',
                            branches: @js($branches),
                            departments: @js($departments),
                            get fB() { return this.branches.filter(b => !this.company || b.company_id == this.company) },
                            get fD() { return this.departments.filter(d => !this.company || d.company_id == this.company) }
                         }">
                        <h2 class="font-semibold text-gray-900 mb-4">Assign & provision account</h2>
                        <form method="POST" action="{{ route('identity.registrations.provision', $application) }}" class="space-y-3"
                              onsubmit="return confirm('Create the account and send the activation invitation?')">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Company *</label>
                                <select name="company_id" x-model="company" required class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    @foreach ($companies as $c)<option value="{{ $c->id }}">{{ $c->legal_name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
                                <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    <option value="">—</option>
                                    <template x-for="b in fB" :key="b.id"><option :value="b.id" x-text="b.name" :selected="b.id == {{ $application->requested_branch_id ?? 'null' }}"></option></template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                                <select name="department_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    <option value="">—</option>
                                    <template x-for="d in fD" :key="d.id"><option :value="d.id" x-text="d.name" :selected="d.id == {{ $application->requested_department_id ?? 'null' }}"></option></template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Position</label>
                                <input name="position" value="{{ $application->job_title }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Employee no.</label>
                                    <input name="employee_number" value="{{ $application->employee_number }}" placeholder="auto" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Manager</label>
                                    <select name="manager_user_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                        <option value="">—</option>
                                        @foreach ($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">System role *</label>
                                <select name="role" required class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    @foreach ($roles as $r)<option value="{{ $r->name }}" @selected($r->name === 'employee')>{{ $r->name }}</option>@endforeach
                                </select>
                            </div>
                            <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                                Provision account & send invitation
                            </button>
                            <x-input-error :messages="$errors->all()" class="mt-1" />
                        </form>
                    </div>
                @endcan
            @endif

            @if ($application->provisioned_user_id)
                <div class="rounded-sm border border-green-200 bg-green-50 p-5 text-sm text-green-800">
                    Provisioned — user:
                    <a class="font-medium underline" href="{{ route('identity.users.show', $application->provisioned_user_id) }}">#{{ $application->provisioned_user_id }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
