<x-app-layout>
    <x-slot name="header">Access Administration</x-slot>

    <div x-data="{ tab: 'branches' }" class="space-y-4">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-6">
                @foreach (['branches' => 'Branch Access', 'departments' => 'Department Access', 'history' => 'Access History'] as $key => $label)
                    <button x-on:click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-1 py-3 text-sm font-medium">{{ $label }}</button>
                @endforeach
            </nav>
        </div>

        <div x-show="tab === 'branches'" class="rounded-sm border border-gray-200 bg-white">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-400">
                    <tr><th class="px-5 py-3">Branch</th><th class="px-5 py-3">Assigned users</th><th class="px-5 py-3">Administrators</th><th class="px-5 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($branches as $b)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $b->name }} <span class="font-mono text-xs text-gray-400">{{ $b->code }}</span></td>
                            <td class="px-5 py-3 text-gray-600">{{ $b->users->count() }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $b->administrators->map(fn ($a) => $a->user->name)->implode(', ') ?: '—' }}</td>
                            <td class="px-5 py-3 text-xs {{ $b->isActive() ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($b->status->value) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'departments'" class="rounded-sm border border-gray-200 bg-white">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-400">
                    <tr><th class="px-5 py-3">Department</th><th class="px-5 py-3">Branch</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($branches->flatMap->departments as $d)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $d->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $branches->firstWhere('id', $d->branch_id)?->name ?? 'Company-wide' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $d->code }}</td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ ucfirst($d->status->value ?? $d->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'history'" class="rounded-sm border border-gray-200 bg-white">
            <ul class="divide-y divide-gray-100">
                @forelse ($history as $log)
                    <li class="px-5 py-3 text-sm">
                        <span class="font-mono text-xs text-gray-900">{{ $log->action }}</span>
                        <span class="ml-2 text-xs text-gray-400">{{ $log->user?->name ?? 'system' }} · {{ $log->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No access events recorded.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
