<x-app-layout>
    <x-slot name="header">{{ $isGlobal ? __('Administration Console — Global') : __('Administration — ') . $branch?->name }}</x-slot>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold">{{ $usersTotal }}</div><div class="text-xs text-gray-500">Users</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold text-green-600">{{ $usersActive }}</div><div class="text-xs text-gray-500">Active</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold text-red-600">{{ $usersSuspended }}</div><div class="text-xs text-gray-500">Suspended</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-2xl font-bold">{{ $branchAdmins->count() }}</div><div class="text-xs text-gray-500">Branch Admins</div></div>
    </div>

    @if ($isGlobal)
    <div class="grid md:grid-cols-2 gap-6">
        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-3">Branches</h3>
            <table class="w-full text-sm"><thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">Code</th><th class="pb-2">Name</th></tr></thead>
            <tbody>@foreach ($branches as $b)<tr class="border-t"><td class="py-1.5 font-mono">{{ $b->code }}</td><td>{{ $b->name }}</td></tr>@endforeach</tbody></table>
            <a href="{{ route('identity.admin.branch-admins') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Manage Branch Administrators →</a>
        </div>
        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-3">Role Distribution</h3>
            <table class="w-full text-sm"><thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">Role</th><th class="pb-2">Users</th></tr></thead>
            <tbody>@foreach ($roleDistribution as $r)<tr class="border-t"><td class="py-1.5">{{ $r->name }}</td><td>{{ $r->users_count }}</td></tr>@endforeach</tbody></table>
        </div>
    </div>
    @else
    <div class="rounded-lg bg-white shadow p-4">
        <h3 class="font-semibold mb-3">Branch Administrators — {{ $branch?->name }}</h3>
        <table class="w-full text-sm"><thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">Name</th><th class="pb-2">Email</th><th class="pb-2">Status</th></tr></thead>
        <tbody>@forelse ($branchAdmins as $d)<tr class="border-t"><td class="py-1.5">{{ $d->user->name }}</td><td>{{ $d->user->email }}</td><td>{{ $d->status }}</td></tr>@empty<tr><td colspan="3" class="py-4 text-center text-gray-400">No branch administrators.</td></tr>@endforelse</tbody></table>
    </div>
    @endif
</x-app-layout>
