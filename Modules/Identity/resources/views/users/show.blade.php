<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span>{{ $user->name }}</span>
            @php $cls = match($user->status?->value) {
                'active' => 'bg-green-100 text-green-700',
                'pending' => 'bg-amber-100 text-amber-700',
                'suspended' => 'bg-red-100 text-red-700',
                default => 'bg-gray-200 text-gray-600',
            }; @endphp
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $cls }}">{{ ucfirst($user->status?->value ?? 'pending') }}</span>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            {{-- Details --}}
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-900">Account details</h2>
                </div>
                <div class="px-5 py-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><p class="text-xs text-gray-500">Email</p><p>{{ $user->email }}</p></div>
                    <div><p class="text-xs text-gray-500">Phone</p><p>{{ $user->phone ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-500">Employee no.</p><p class="font-mono">{{ $user->profile?->employee_number ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-500">Job title</p><p>{{ $user->profile?->job_title ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-500">Employment type</p><p>{{ $user->profile?->employment_type ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-500">Email verified</p><p>{{ $user->email_verified_at?->format('d M Y') ?? 'No' }}</p></div>
                </div>
                @can('users.update')
                    <form method="POST" action="{{ route('identity.users.update', $user) }}" class="px-5 py-4 border-t border-gray-100 grid grid-cols-2 gap-3">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $user->name }}" required class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="Name">
                        <input name="phone" value="{{ $user->phone }}" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="Phone">
                        <input name="employee_number" value="{{ $user->profile?->employee_number }}" class="rounded-md border-gray-300 text-sm shadow-sm font-mono" placeholder="Employee no.">
                        <input name="job_title" value="{{ $user->profile?->job_title }}" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="Job title">
                        <div class="col-span-2"><button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Save changes</button></div>
                    </form>
                @endcan
            </div>

            {{-- Assignment history --}}
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Organizational assignment history</h2></div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                        <tr><th class="px-5 py-2">Placement</th><th class="px-5 py-2">Position</th><th class="px-5 py-2">Period</th><th class="px-5 py-2">Type</th><th class="px-5 py-2">By</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($user->assignments->sortByDesc('effective_start') as $a)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-medium">{{ $a->company->legal_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $a->branch?->name ?? '—' }} / {{ $a->department?->name ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-3">{{ $a->position ?? '—' }}</td>
                                <td class="px-5 py-3 text-xs text-gray-500">{{ $a->effective_start->format('d M Y') }} → {{ $a->effective_end?->format('d M Y') ?? 'present' }}</td>
                                <td class="px-5 py-3"><span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs {{ $a->status === 'active' ? 'text-green-700 bg-green-100' : 'text-gray-600' }}">{{ str_replace('_', ' ', $a->assignment_type) }}</span></td>
                                <td class="px-5 py-3 text-xs text-gray-500">{{ $a->assignedBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-6 text-center text-gray-400">No assignments recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @can('users.assignments.manage')
                    <form method="POST" action="{{ route('identity.users.assignments', $user) }}"
                          class="px-5 py-4 border-t border-gray-100 space-y-3"
                          x-data="{ company: '{{ $user->currentAssignment?->company_id }}', branches: @js($branches), departments: @js($departments),
                                    get fB() { return this.branches.filter(b => !this.company || b.company_id == this.company) },
                                    get fD() { return this.departments.filter(d => !this.company || d.company_id == this.company) } }">
                        @csrf
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">New assignment / transfer</p>
                        <div class="grid grid-cols-3 gap-3">
                            <select name="company_id" x-model="company" required class="rounded-md border-gray-300 text-sm shadow-sm">
                                @foreach ($companies as $c)<option value="{{ $c->id }}">{{ $c->legal_name }}</option>@endforeach
                            </select>
                            <select name="branch_id" class="rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Branch —</option>
                                <template x-for="b in fB" :key="b.id"><option :value="b.id" x-text="b.name"></option></template>
                            </select>
                            <select name="department_id" class="rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Dept —</option>
                                <template x-for="d in fD" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                            </select>
                        </div>
                        <div class="grid grid-cols-4 gap-3">
                            <input name="position" placeholder="Position" class="rounded-md border-gray-300 text-sm shadow-sm">
                            <select name="manager_user_id" class="rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Manager —</option>
                                @foreach ($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                            </select>
                            <select name="assignment_type" required class="rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="transfer">Transfer</option>
                                <option value="position_change">Position change</option>
                                <option value="temporary">Temporary</option>
                                <option value="new">New</option>
                                <option value="termination">Termination</option>
                            </select>
                            <input type="date" name="effective_start" required class="rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                        <input name="reason" placeholder="Reason for change" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Record assignment</button>
                        <x-input-error :messages="$errors->all()" class="mt-1" />
                    </form>
                @endcan
            </div>

            {{-- Login history + security events --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900 text-sm">Login history</h2></div>
                    <ul class="divide-y divide-gray-100 text-xs">
                        @forelse ($user->loginHistories as $h)
                            <li class="px-5 py-2.5 flex justify-between">
                                <span class="{{ $h->event === 'failed' ? 'text-red-600' : 'text-gray-700' }}">{{ $h->event }}</span>
                                <span class="text-gray-400">{{ $h->created_at->diffForHumans(short: true) }} · {{ $h->ip_address }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-gray-400">No logins recorded.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900 text-sm">Security events</h2></div>
                    <ul class="divide-y divide-gray-100 text-xs">
                        @forelse ($user->securityEvents as $e)
                            <li class="px-5 py-2.5 flex justify-between">
                                <span class="{{ $e->severity === 'warning' ? 'text-amber-600' : 'text-gray-700' }}">{{ str_replace('_', ' ', $e->type) }}</span>
                                <span class="text-gray-400">{{ $e->created_at->diffForHumans(short: true) }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-gray-400">No security events.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right: actions + roles --}}
        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                <h2 class="font-semibold text-gray-900">Access control</h2>

                @if ($user->status !== \App\Enums\AccountStatus::Active)
                    @can('users.activate')
                        <form method="POST" action="{{ route('identity.users.activate', $user) }}">
                            @csrf
                            <button class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-500">Activate account</button>
                        </form>
                    @endcan
                @endif

                @if ($user->status === \App\Enums\AccountStatus::Pending && ! $pendingInvitation)
                    @can('registration.provision')
                        <form method="POST" action="{{ route('identity.users.resend-invitation', $user) }}">
                            @csrf
                            <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Send activation invitation</button>
                        </form>
                    @endcan
                @elseif ($pendingInvitation)
                    <p class="rounded-md bg-indigo-50 border border-indigo-200 px-3 py-2 text-xs text-indigo-700">
                        Invitation pending — expires {{ $pendingInvitation->expires_at->diffForHumans() }}.
                    </p>
                @endif

                @if ($user->status === \App\Enums\AccountStatus::Active)
                    @can('users.suspend')
                        <form method="POST" action="{{ route('identity.users.suspend', $user) }}"
                              onsubmit="return confirm('Suspend {{ $user->name }}? All sessions and tokens will be revoked.')">
                            @csrf
                            <input name="reason" placeholder="Reason (optional)" class="w-full rounded-md border-gray-300 text-sm shadow-sm mb-2">
                            <button class="w-full rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-500">Suspend account</button>
                        </form>
                    @endcan
                @endif

                @can('users.sessions.revoke')
                    <form method="POST" action="{{ route('identity.users.revoke-sessions', $user) }}"
                          onsubmit="return confirm('Revoke all sessions and API tokens for this user?')">
                        @csrf
                        <button class="w-full rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-600">Revoke sessions & tokens</button>
                    </form>
                @endcan

                @can('users.deactivate')
                    <form method="POST" action="{{ route('identity.users.deactivate', $user) }}"
                          onsubmit="return confirm('Deactivate this account permanently?')">
                        @csrf
                        <input name="reason" placeholder="Reason (optional)" class="w-full rounded-md border-gray-300 text-sm shadow-sm mb-2">
                        <button class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Deactivate account</button>
                    </form>
                @endcan
            </div>

            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                <h2 class="font-semibold text-gray-900">Roles</h2>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($user->getRoleNames() as $r)
                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $r }}</span>
                    @empty
                        <p class="text-sm text-gray-400">No roles assigned.</p>
                    @endforelse
                </div>
                @can('roles.assign')
                    <form method="POST" action="{{ route('identity.users.role', $user) }}" class="flex gap-2">
                        @csrf
                        <select name="role" required class="flex-1 rounded-md border-gray-300 text-sm shadow-sm">
                            @foreach ($roles as $r)<option value="{{ $r->name }}">{{ $r->name }}</option>@endforeach
                        </select>
                        <button class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">Set</button>
                    </form>
                @endcan
                <p class="text-xs text-gray-400">{{ $user->getAllPermissions()->count() }} effective permissions</p>
            </div>
        </div>
    </div>
</x-app-layout>
