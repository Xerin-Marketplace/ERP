<x-app-layout>
    <x-slot name="header">{{ $employee->full_name }} <span class="text-sm font-normal text-gray-400">· {{ $employee->employee_number }}</span></x-slot>

    @php
        $tabs = [
            'overview' => 'Overview', 'personal' => 'Personal', 'employment' => 'Employment',
            'assignment' => 'Assignment', 'documents' => 'Documents', 'history' => 'History',
            'reporting' => 'Reporting', 'onboarding' => 'Onboarding', 'activity' => 'Activity',
        ];
    @endphp

    <div class="mb-4 flex flex-wrap gap-2 border-b border-gray-200">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('employee.show', [$employee, 'tab' => $key]) }}"
               class="px-3 py-2 text-sm font-medium {{ $tab === $key ? 'border-b-2 border-xerin-600 text-xerin-700' : 'text-gray-500 hover:text-gray-700' }}">{{ $label }}</a>
        @endforeach
        <span class="flex-1"></span>
        @can('employee.update')
            <a href="{{ route('employee.edit', $employee) }}" class="px-3 py-2 text-sm font-semibold text-xerin-700 hover:underline">Edit</a>
        @endcan
    </div>

    @if ($tab === 'overview')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm md:col-span-2">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Employment Summary</h3>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div><dt class="text-gray-500">Status</dt><dd class="font-medium">{{ ucfirst($employee->status) }}</dd></div>
                    <div><dt class="text-gray-500">Type</dt><dd class="font-medium">{{ ucwords(str_replace('_',' ',$employee->employment_type)) }}</dd></div>
                    <div><dt class="text-gray-500">Company</dt><dd class="font-medium">{{ $employee->company?->legal_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Branch</dt><dd class="font-medium">{{ $employee->branch?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd class="font-medium">{{ $employee->department?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Position</dt><dd class="font-medium">{{ $employee->position?->title ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Grade</dt><dd class="font-medium">{{ $employee->position?->grade?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Manager</dt><dd class="font-medium">{{ $employee->manager?->full_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Start Date</dt><dd class="font-medium">{{ $employee->start_date?->toFormattedDateString() ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Contract End</dt><dd class="font-medium">{{ $employee->contract_end_date?->toFormattedDateString() ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="space-y-4">
                <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">ERP Account</h3>
                    @if ($employee->user)
                        <p class="text-sm">{{ $employee->user->email }}</p>
                        <p class="text-xs text-gray-400">Status: {{ $employee->user->status->value }}</p>
                        <p class="text-xs text-gray-400">Roles: {{ $employee->user->roles->pluck('name')->join(', ') ?: '—' }}</p>
                    @else
                        <p class="text-sm text-gray-400">No linked ERP account.</p>
                    @endif
                </div>
                <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Onboarding</h3>
                    @if ($employee->onboarding)
                        <div class="h-2 rounded bg-gray-100 overflow-hidden"><div class="h-2 bg-xerin-600" style="width:{{ $employee->onboarding->progress() }}%"></div></div>
                        <p class="mt-1 text-xs text-gray-500">{{ $employee->onboarding->progress() }}% · {{ ucfirst(str_replace('_',' ',$employee->onboarding->status)) }}</p>
                    @else
                        <p class="text-sm text-gray-400">Not started.</p>
                    @endif
                </div>
            </div>
        </div>

    @elseif ($tab === 'personal')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Personal Details</h3>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                <div><dt class="text-gray-500">DOB</dt><dd>{{ $employee->personal?->date_of_birth?->toFormattedDateString() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Gender</dt><dd>{{ ucfirst($employee->personal?->gender ?? '—') }}</dd></div>
                <div><dt class="text-gray-500">Nationality</dt><dd>{{ $employee->personal?->nationality ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">National ID</dt><dd>{{ $employee->personal?->national_id ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Personal Email</dt><dd>{{ $employee->personal?->personal_email ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd>{{ $employee->personal?->phone ?? '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Address</dt><dd>{{ $employee->personal?->residential_address ?? '—' }}</dd></div>
            </dl>
            <h4 class="text-sm font-semibold text-gray-900 mt-6 mb-2">Emergency Contacts</h4>
            <ul class="text-sm space-y-1">
                @forelse ($employee->emergencyContacts as $c)
                    <li>{{ $c->name }} <span class="text-gray-400">({{ $c->relationship }})</span> — {{ $c->phone }}</li>
                @empty
                    <li class="text-gray-400">None recorded.</li>
                @endforelse
            </ul>
        </div>

    @elseif ($tab === 'employment')
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Reference</th>
                    <th class="px-4 py-3 text-left">Start</th><th class="px-4 py-3 text-left">End</th>
                    <th class="px-4 py-3 text-left">Confirmation</th><th class="px-4 py-3 text-left">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employee->employmentRecords as $r)
                        <tr>
                            <td class="px-4 py-3">{{ ucfirst($r->type) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $r->contract_reference ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $r->start_date?->toDateString() ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $r->end_date?->toDateString() ?? '—' }}</td>
                            <td class="px-4 py-3">{{ ucfirst($r->confirmation_status) }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $r->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($r->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No employment records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($tab === 'assignment')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Current Placement</h3>
            <p class="text-sm">{{ $employee->company?->legal_name ?? '—' }} → {{ $employee->branch?->name ?? '—' }} → {{ $employee->department?->name ?? '—' }} → {{ $employee->position?->title ?? '—' }}</p>
            <h4 class="text-sm font-semibold text-gray-900 mt-6 mb-2">IAM Assignment History <span class="text-xs font-normal text-gray-400">(Identity module)</span></h4>
            <ul class="text-sm space-y-2">
                @forelse ($employee->user?->assignments ?? [] as $a)
                    <li class="border-l-2 {{ $a->effective_end ? 'border-gray-200' : 'border-xerin-500' }} pl-3">
                        {{ $a->company?->legal_name }} / {{ $a->branch?->name }} / {{ $a->department?->name }}
                        <span class="text-xs text-gray-400">{{ $a->effective_start?->toDateString() }} → {{ $a->effective_end?->toDateString() ?? 'current' }} · {{ $a->assignment_type }}</span>
                    </li>
                @empty
                    <li class="text-gray-400">No IAM assignment history (no linked account).</li>
                @endforelse
            </ul>
        </div>

    @elseif ($tab === 'documents')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Documents ({{ $employee->documents_count }})</h3>
                <a href="{{ route('employee.documents.index', $employee) }}" class="text-sm font-semibold text-xerin-700 hover:underline">Manage →</a>
            </div>
            <ul class="text-sm divide-y divide-gray-100">
                @forelse ($employee->documents as $doc)
                    <li class="py-2 flex justify-between">
                        <span>{{ $doc->title }} <span class="text-xs text-gray-400">v{{ $doc->version }} · {{ $doc->category->name }}</span></span>
                        <a href="{{ route('employee.documents.download', $doc) }}" class="text-xerin-700 text-xs font-semibold">Download</a>
                    </li>
                @empty
                    <li class="py-4 text-gray-400">No documents.</li>
                @endforelse
            </ul>
        </div>

    @elseif ($tab === 'history')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Transfers</h3>
                <ul class="text-sm divide-y divide-gray-100">
                    @forelse ($employee->transfers as $t)
                        <li class="py-2 flex justify-between">
                            <span>{{ ucfirst($t->type) }} → {{ $t->toCompany?->legal_name ?? $t->toBranch?->name ?? '—' }}
                                <span class="text-xs text-gray-400">by {{ $t->requester?->name }}</span></span>
                            <span class="text-xs font-medium {{ $t->status === 'approved' ? 'text-green-600' : ($t->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($t->status) }}</span>
                        </li>
                    @empty <li class="py-2 text-gray-400">None.</li> @endforelse
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Employment Changes</h3>
                <ul class="text-sm divide-y divide-gray-100">
                    @forelse ($employee->changeRequests as $c)
                        <li class="py-2 flex justify-between">
                            <span>{{ ucwords(str_replace('_',' ',$c->type)) }} <span class="text-xs text-gray-400">by {{ $c->initiator?->name }}</span></span>
                            <span class="text-xs font-medium {{ $c->status === 'approved' ? 'text-green-600' : ($c->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($c->status) }}</span>
                        </li>
                    @empty <li class="py-2 text-gray-400">None.</li> @endforelse
                </ul>
            </div>
        </div>

    @elseif ($tab === 'reporting')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Reports To</h3>
            <p class="text-sm mb-4">{{ $employee->manager?->full_name ?? '—' }}</p>
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Direct Reports</h3>
            <ul class="text-sm space-y-1">
                @forelse ($directReports as $r)
                    <li><a href="{{ route('employee.show', $r) }}" class="text-xerin-700 hover:underline">{{ $r->full_name }}</a>
                        <span class="text-xs text-gray-400">{{ $r->position?->title }}</span></li>
                @empty <li class="text-gray-400">None.</li> @endforelse
            </ul>
        </div>

    @elseif ($tab === 'onboarding')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            @if ($employee->onboarding)
                <ul class="text-sm space-y-2">
                    @foreach ($employee->onboarding->tasks as $task)
                        <li class="flex justify-between">
                            <span class="{{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">{{ $task->title }}</span>
                            <span class="text-xs text-gray-400">{{ ucfirst($task->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-400">Onboarding not started.</p>
            @endif
        </div>

    @else
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Activity Timeline</h3>
            <ul class="text-sm space-y-3">
                @forelse ($employee->events as $ev)
                    <li class="border-l-2 border-gray-200 pl-3">
                        <p>{{ $ev->description }}</p>
                        <p class="text-xs text-gray-400">{{ $ev->created_at->diffForHumans() }} · {{ $ev->actor?->name ?? 'System' }}</p>
                    </li>
                @empty
                    <li class="text-gray-400">No activity.</li>
                @endforelse
            </ul>
        </div>
    @endif
</x-app-layout>
