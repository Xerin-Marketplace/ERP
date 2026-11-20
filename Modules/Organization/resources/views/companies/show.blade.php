<x-app-layout>
    <x-slot name="header">{{ $company->trading_name ?: $company->legal_name }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Details -->
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $company->legal_name }}</h2>
                        <p class="mt-1 text-sm text-gray-500 font-mono">{{ $company->code }} · {{ ucfirst($company->company_type) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @include('organization::partials.status-badge', ['status' => $company->status])
                        @can('organization.company.update')
                            <a href="{{ route('organization.companies.edit', $company) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                        @endcan
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    @foreach ([
                        'Parent company' => $company->parent?->legal_name,
                        'Registration no.' => $company->registration_number,
                        'TIN' => $company->tin,
                        'VAT no.' => $company->vat_number,
                        'Email' => $company->email,
                        'Phone' => $company->phone,
                        'Website' => $company->website,
                        'Country' => $company->country,
                        'Region' => $company->region,
                        'District' => $company->district,
                        'City' => $company->city,
                        'Physical address' => $company->physical_address,
                        'Postal address' => $company->postal_address,
                        'Currency' => $company->currency,
                        'Timezone' => $company->timezone,
                        'Operational start' => $company->operational_start_date?->format('d M Y'),
                        'Created by' => $company->creator?->name,
                        'Created' => $company->created_at->format('d M Y H:i'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <!-- Branches -->
            <div class="rounded-sm border border-gray-200 bg-white">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Branches ({{ $company->branches->count() }})</h3>
                    @can('organization.branches.create')
                        <a href="{{ route('organization.branches.create') }}" class="text-xs font-medium text-indigo-600">+ Add</a>
                    @endcan
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($company->branches as $b)
                        <li class="flex items-center justify-between px-5 py-3">
                            <a href="{{ route('organization.branches.show', $b) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $b->name }}</a>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                {{ $b->city ?? '—' }} @include('organization::partials.status-badge', ['status' => $b->status])
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-400 text-center">No branches.</li>
                    @endforelse
                </ul>
            </div>

            <!-- Departments -->
            <div class="rounded-sm border border-gray-200 bg-white">
                <div class="px-5 py-4 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-900">Departments ({{ $company->departments->count() }})</h3></div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($company->departments as $d)
                        <li class="flex items-center justify-between px-5 py-3">
                            <a href="{{ route('organization.departments.show', $d) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $d->name }}</a>
                            @include('organization::partials.status-badge', ['status' => $d->status])
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-400 text-center">No departments.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Side: status + subsidiaries -->
        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Status actions</h3>
                @include('organization::partials.status-actions', [
                    'route' => route('organization.companies.status', $company),
                    'status' => $company->status,
                ])
            </div>

            <div class="rounded-sm border border-gray-200 bg-white">
                <div class="px-5 py-4 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-900">Subsidiaries ({{ $company->subsidiaries->count() }})</h3></div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($company->subsidiaries as $s)
                        <li class="flex items-center justify-between px-5 py-3">
                            <a href="{{ route('organization.companies.show', $s) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $s->legal_name }}</a>
                            @include('organization::partials.status-badge', ['status' => $s->status])
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-400 text-center">No subsidiaries.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
