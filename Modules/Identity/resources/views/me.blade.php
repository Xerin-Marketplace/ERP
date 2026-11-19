<x-app-layout>
    <x-slot name="header">{{ __('My Identity') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="font-semibold text-gray-900 mb-4">Profile</h2>
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><p class="text-xs text-gray-500">Name</p><p class="font-medium">{{ $user->name }}</p></div>
                    <div><p class="text-xs text-gray-500">Email</p><p>{{ $user->email }}</p></div>
                    <div><p class="text-xs text-gray-500">Phone</p><p>{{ $user->phone ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-500">Employee no.</p><p class="font-mono">{{ $user->profile?->employee_number ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-500">Job title</p><p>{{ $user->profile?->job_title ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-500">Account status</p><p>{{ ucfirst($user->status?->value ?? '—') }}</p></div>
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="font-semibold text-gray-900 mb-4">Current placement</h2>
                @if ($user->currentAssignment)
                    <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div><p class="text-xs text-gray-500">Company</p><p class="font-medium">{{ $user->currentAssignment->company->legal_name }}</p></div>
                        <div><p class="text-xs text-gray-500">Branch</p><p>{{ $user->currentAssignment->branch?->name ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Department</p><p>{{ $user->currentAssignment->department?->name ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Position</p><p>{{ $user->currentAssignment->position ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Reports to</p><p>{{ $user->currentAssignment->manager?->name ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Since</p><p>{{ $user->currentAssignment->effective_start->format('d M Y') }}</p></div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No organizational assignment yet — contact IT.</p>
                @endif
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="font-semibold text-gray-900 mb-3">Assignment history</h2>
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse ($user->assignments->sortByDesc('effective_start') as $a)
                        <li class="py-2.5 flex justify-between">
                            <div>
                                <p class="font-medium">{{ $a->company->legal_name }}</p>
                                <p class="text-xs text-gray-500">{{ $a->branch?->name ?? '—' }} / {{ $a->department?->name ?? '—' }} · {{ $a->position ?? '—' }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $a->effective_start->format('M Y') }} — {{ $a->effective_end?->format('M Y') ?? 'now' }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-center text-gray-400">No history.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="font-semibold text-gray-900 mb-3">Access</h2>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @foreach ($user->getRoleNames() as $r)
                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $r }}</span>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400">{{ $user->getAllPermissions()->count() }} effective permissions</p>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="font-semibold text-gray-900 mb-3">Recent logins</h2>
                <ul class="space-y-2 text-xs">
                    @forelse ($user->loginHistories as $h)
                        <li class="flex justify-between">
                            <span class="{{ $h->event === 'failed' ? 'text-red-600' : 'text-gray-700' }}">{{ $h->event }}</span>
                            <span class="text-gray-400">{{ $h->created_at->diffForHumans(short: true) }}</span>
                        </li>
                    @empty
                        <li class="text-gray-400">No logins recorded.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="font-semibold text-gray-900 mb-3">Notifications</h2>
                <ul class="space-y-2 text-xs">
                    @forelse ($user->unreadNotifications as $n)
                        <li class="flex justify-between gap-2">
                            <span class="text-gray-700">{{ $n->data['subject'] ?? $n->data['type'] ?? 'Notification' }}</span>
                            <span class="text-gray-400 shrink-0">{{ $n->created_at->diffForHumans(short: true) }}</span>
                        </li>
                    @empty
                        <li class="text-gray-400">No new notifications.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
