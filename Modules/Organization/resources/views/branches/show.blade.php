<x-app-layout>
    <x-slot name="header">{{ $branch->name }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $branch->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500 font-mono">{{ $branch->code }} · {{ str_replace('_',' ',ucfirst($branch->branch_type)) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @include('organization::partials.status-badge', ['status' => $branch->status])
                        @can('branch.update')
                            <a href="{{ route('organization.branches.edit', $branch) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                        @endcan
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    @foreach ([
                        'Company' => $branch->company?->legal_name,
                        'Contact person' => $branch->contact_person,
                        'Email' => $branch->email,
                        'Phone' => $branch->phone,
                        'Country' => $branch->country,
                        'Region' => $branch->region,
                        'District' => $branch->district,
                        'City' => $branch->city,
                        'Physical address' => $branch->physical_address,
                        'Postal address' => $branch->postal_address,
                        'GPS' => $branch->latitude ? $branch->latitude.', '.$branch->longitude : null,
                        'Opening date' => $branch->opening_date?->format('d M Y'),
                        'Staff assigned' => $branch->users->count(),
                        'Created by' => $branch->creator?->name,
                        'Created' => $branch->created_at->format('d M Y H:i'),
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

            <div class="rounded-sm border border-gray-200 bg-white">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Departments ({{ $branch->departments->count() }})</h3>
                    @can('department.create')
                        <a href="{{ route('organization.departments.create') }}" class="text-xs font-medium text-indigo-600">+ Add</a>
                    @endcan
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($branch->departments as $d)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between">
                                <a href="{{ route('organization.departments.show', $d) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $d->name }}</a>
                                @include('organization::partials.status-badge', ['status' => $d->status])
                            </div>
                            @foreach ($d->children as $child)
                                <div class="ml-4 mt-1 text-xs text-gray-500">↳ {{ $child->name }}</div>
                            @endforeach
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-400 text-center">No departments in this branch.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Status actions</h3>
                @include('organization::partials.status-actions', [
                    'route' => route('organization.branches.status', $branch),
                    'status' => $branch->status,
                ])
            </div>

            <div class="rounded-sm border border-gray-200 bg-white">
                <div class="px-5 py-4 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-900">Locations ({{ $branch->locations->count() }})</h3></div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($branch->locations as $l)
                        <li class="px-5 py-3 text-sm text-gray-700">{{ $l->name }} <span class="text-xs text-gray-400">· {{ str_replace('_',' ',$l->location_type) }}</span></li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-400 text-center">No locations.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
