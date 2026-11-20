<x-app-layout>
    <x-slot name="header">{{ $branch->name }}</x-slot>

    @php
        $tabs = ['overview' => 'Overview', 'departments' => 'Departments ('.$branch->departments->count().')',
                 'positions' => 'Positions', 'administrators' => 'Administrators',
                 'settings' => 'Settings', 'activity' => 'Activity'];
    @endphp

    <div x-data="{ tab: 'overview' }" class="space-y-4">
        {{-- Header card --}}
        <div class="rounded-sm border border-gray-200 bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $branch->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500 font-mono">{{ $branch->code }} · {{ str_replace('_',' ',ucfirst($branch->branch_type)) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $branch->country }}{{ $branch->city ? ' · '.$branch->city : '' }} · {{ $branch->currency ?? '—' }} · {{ $branch->timezone ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @include('organization::partials.status-badge', ['status' => $branch->status])
                    @can('organization.branches.update')
                        <a href="{{ route('organization.branches.edit', $branch) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                    @endcan
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                @if ($branch->isActive())
                    @can('organization.branches.deactivate')
                        <form method="POST" action="{{ route('organization.branches.deactivate', $branch) }}"
                              onsubmit="return confirm('Deactivate {{ $branch->name }}? New operational assignments will be blocked; history is preserved.')">
                            @csrf
                            <button class="rounded-sm border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">Deactivate</button>
                        </form>
                    @endcan
                @else
                    @can('organization.branches.activate')
                        <form method="POST" action="{{ route('organization.branches.activate', $branch) }}">
                            @csrf
                            <button class="rounded-sm bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">Activate</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-6 overflow-x-auto">
                @foreach ($tabs as $key => $label)
                    <button x-on:click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium">{{ $label }}</button>
                @endforeach
            </nav>
        </div>

        {{-- Overview --}}
        <div x-show="tab === 'overview'" class="rounded-sm border border-gray-200 bg-white p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                @foreach ([
                    'Company' => $branch->company?->legal_name,
                    'Contact person' => $branch->contact_person,
                    'Email' => $branch->email,
                    'Phone' => $branch->phone,
                    'Country' => $branch->country.($branch->country_code ? ' ('.$branch->country_code.')' : ''),
                    'Region' => $branch->region,
                    'District' => $branch->district,
                    'City' => $branch->city,
                    'Physical address' => $branch->physical_address,
                    'Postal code' => $branch->postal_code,
                    'Currency' => $branch->currency,
                    'Timezone' => $branch->timezone,
                    'Local time' => $branch->timezone ? now()->setTimezone($branch->timezone)->format('H:i, d M Y') : null,
                    'GPS' => $branch->latitude ? $branch->latitude.', '.$branch->longitude : null,
                    'Opening date' => $branch->opening_date?->format('d M Y'),
                    'Staff assigned' => $branch->users->count(),
                    'Activated' => $branch->activated_at?->format('d M Y H:i'),
                    'Deactivated' => $branch->deactivated_at?->format('d M Y H:i'),
                    'Created by' => $branch->creator?->name,
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ $label }}</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $value !== null && $value !== '' ? $value : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
            @if ($branch->description)
                <p class="mt-6 text-sm text-gray-600 border-t border-gray-100 pt-4">{{ $branch->description }}</p>
            @endif
        </div>

        {{-- Departments --}}
        <div x-show="tab === 'departments'" class="rounded-sm border border-gray-200 bg-white">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Departments in this branch</h3>
                @can('organization.departments.create')
                    <a href="{{ route('organization.departments.create', ['branch' => $branch->id]) }}" class="text-xs font-medium text-indigo-600">+ Add department</a>
                @endcan
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($branch->departments as $d)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <a href="{{ route('organization.departments.show', $d) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $d->name }}</a>
                            <span class="ml-2 font-mono text-xs text-gray-400">{{ $d->code }}</span>
                        </div>
                        @include('organization::partials.status-badge', ['status' => $d->status])
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No departments yet for this branch.</li>
                @endforelse
            </ul>
        </div>

        {{-- Positions --}}
        <div x-show="tab === 'positions'" class="rounded-sm border border-gray-200 bg-white">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Positions at this branch</h3>
                @can('organization.positions.create')
                    <a href="{{ route('organization.positions.create') }}" class="text-xs font-medium text-indigo-600">+ Add position</a>
                @endcan
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($positions as $p)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ $p->title }}</span>
                            <span class="ml-2 font-mono text-xs text-gray-400">{{ $p->code }}</span>
                        </div>
                        <span class="text-xs text-gray-500">{{ ucfirst($p->status) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No positions linked to this branch's departments.</li>
                @endforelse
            </ul>
        </div>

        {{-- Administrators --}}
        <div x-show="tab === 'administrators'" class="rounded-sm border border-gray-200 bg-white">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Branch administrators</h3>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($branch->administrators->whereNull('revoked_at') as $admin)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ $admin->user->name }}</span>
                            <span class="ml-2 text-xs text-gray-400">{{ $admin->user->email }}</span>
                        </div>
                        @can('organization.branch_administrators.revoke')
                            <form method="POST" action="{{ route('organization.branches.administrators.revoke', [$branch, $admin]) }}"
                                  onsubmit="return confirm('Revoke this administrator?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-600 hover:text-red-800">Revoke</button>
                            </form>
                        @endcan
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No administrators assigned.</li>
                @endforelse
            </ul>
            @can('organization.branch_administrators.assign')
                <form method="POST" action="{{ route('organization.branches.administrators.assign', $branch) }}"
                      class="flex items-center gap-3 border-t border-gray-100 px-5 py-4">
                    @csrf
                    <select name="user_id" required class="w-72 rounded-sm border-gray-300 text-sm">
                        <option value="">Select user…</option>
                        @foreach ($candidates as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-sm bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Assign administrator</button>
                </form>
            @endcan
        </div>

        {{-- Settings --}}
        <div x-show="tab === 'settings'" class="rounded-sm border border-gray-200 bg-white p-6">
            @php $settings = $branch->settings->mapWithKeys(fn ($s) => [$s->key => $s->typedValue()]); @endphp
            @can('organization.branches.manage')
                <form method="POST" action="{{ route('organization.branches.settings.update', $branch) }}" class="space-y-5">
                    @csrf @method('PUT')
                    <div>
                        <label class="text-xs font-medium uppercase tracking-wider text-gray-400">Working days</label>
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach (['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'] as $key => $day)
                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                    <input type="checkbox" name="working_days[]" value="{{ $key }}"
                                           @checked(in_array($key, $settings['working_days'] ?? ['mon','tue','wed','thu','fri']))
                                           class="rounded border-gray-300 text-indigo-600">
                                    {{ $day }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 max-w-md">
                        <div>
                            <label class="text-xs font-medium uppercase tracking-wider text-gray-400">Business open</label>
                            <input type="time" name="business_open" value="{{ $settings['business_open'] ?? '08:00' }}"
                                   class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-medium uppercase tracking-wider text-gray-400">Business close</label>
                            <input type="time" name="business_close" value="{{ $settings['business_close'] ?? '17:00' }}"
                                   class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                        </div>
                    </div>
                    <button class="rounded-sm bg-indigo-600 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-700">Save settings</button>
                </form>
            @else
                <dl class="grid grid-cols-3 gap-4 text-sm">
                    <div><dt class="text-xs uppercase text-gray-400">Working days</dt>
                        <dd>{{ implode(', ', $settings['working_days'] ?? ['mon','tue','wed','thu','fri']) }}</dd></div>
                    <div><dt class="text-xs uppercase text-gray-400">Opens</dt><dd>{{ $settings['business_open'] ?? '08:00' }}</dd></div>
                    <div><dt class="text-xs uppercase text-gray-400">Closes</dt><dd>{{ $settings['business_close'] ?? '17:00' }}</dd></div>
                </dl>
            @endcan
        </div>

        {{-- Activity --}}
        <div x-show="tab === 'activity'" class="rounded-sm border border-gray-200 bg-white">
            <ul class="divide-y divide-gray-100">
                @forelse ($activity as $log)
                    <li class="px-5 py-3 text-sm">
                        <span class="font-medium text-gray-900">{{ $log->action }}</span>
                        <span class="ml-2 text-xs text-gray-400">{{ $log->created_at->diffForHumans() }} · {{ $log->user?->name ?? 'system' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No activity recorded yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
