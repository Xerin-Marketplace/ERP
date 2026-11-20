<x-app-layout>
    <x-slot name="header">{{ $department->name }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $department->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500 font-mono">{{ $department->code }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @include('organization::partials.status-badge', ['status' => $department->status])
                        @can('organization.departments.update')
                            <a href="{{ route('organization.departments.edit', $department) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                        @endcan
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    @foreach ([
                        'Classification' => $department->definition?->name,
                        'Company' => $department->company?->legal_name,
                        'Branch' => $department->branch?->name ?? 'Company-wide',
                        'Parent department' => $department->parent?->name,
                        'Email' => $department->email,
                        'Phone' => $department->phone,
                        'Effective date' => $department->effective_date?->format('d M Y'),
                        'Created by' => $department->creator?->name,
                        'Created' => $department->created_at->format('d M Y H:i'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($department->description)
                    <p class="mt-6 text-sm text-gray-600 border-t border-gray-100 pt-4">{{ $department->description }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Status actions</h3>
                @include('organization::partials.status-actions', [
                    'route' => route('organization.departments.status', $department),
                    'status' => $department->status,
                ])
            </div>

            <div class="rounded-sm border border-gray-200 bg-white">
                <div class="px-5 py-4 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-900">Sub-departments ({{ $department->children->count() }})</h3></div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($department->children as $child)
                        <li class="flex items-center justify-between px-5 py-3">
                            <a href="{{ route('organization.departments.show', $child) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $child->name }}</a>
                            @include('organization::partials.status-badge', ['status' => $child->status])
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-400 text-center">No sub-departments.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
