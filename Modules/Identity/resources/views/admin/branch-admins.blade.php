<x-app-layout>
    <x-slot name="header">{{ __('Branch Administrators') }}</x-slot>

    <div class="rounded-lg bg-white shadow p-4 mb-6">
        <h3 class="font-semibold mb-3">Delegate Branch Administration</h3>
        <form method="POST" action="{{ route('identity.admin.branch-admins.assign') }}" class="grid md:grid-cols-2 gap-4">
            @csrf
            <div><label class="block text-xs font-medium text-gray-500 mb-1">User</label>
                <select name="user_id" class="w-full rounded-md border-gray-300 text-sm" required>
                    @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>@endforeach
                </select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
                <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm" required>
                    @foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Delegable Roles (may assign)</label>
                <select name="delegable_roles[]" multiple size="4" class="w-full rounded-md border-gray-300 text-sm">
                    @foreach ($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                </select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Delegable Permissions</label>
                <select name="delegable_permissions[]" multiple size="4" class="w-full rounded-md border-gray-300 text-sm">
                    @foreach ($permissions as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                </select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Access Expires (optional)</label>
                <input type="datetime-local" name="expires_at" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div class="flex items-end"><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white">Delegate</button></div>
        </form>
    </div>

    <div class="rounded-lg bg-white shadow p-4">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500"><th class="pb-2">Admin</th><th class="pb-2">Branch</th><th class="pb-2">Delegable Roles</th><th class="pb-2">Status</th><th class="pb-2">Assigned By</th><th class="pb-2"></th></tr></thead>
            <tbody>
            @foreach ($delegations as $d)
                <tr class="border-t">
                    <td class="py-2">{{ $d->user->name }}<div class="text-xs text-gray-400">{{ $d->user->email }}</div></td>
                    <td>{{ $d->branch->name }}</td>
                    <td class="text-xs">{{ implode(', ', $d->delegable_roles ?? []) ?: '—' }}</td>
                    <td><span class="rounded px-1.5 py-0.5 text-xs {{ $d->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $d->status }}</span>
                        @if($d->revoked_at)<span class="text-xs text-gray-400">revoked</span>@endif</td>
                    <td class="text-xs">{{ $d->assigner->name ?? '—' }}</td>
                    <td class="text-right space-x-2">
                        @if (! $d->revoked_at)
                        <form method="POST" action="{{ route('identity.admin.branch-admins.' . ($d->status === 'active' ? 'suspend' : 'reactivate'), $d) }}" class="inline">@csrf
                            <button class="text-xs text-amber-600 hover:underline">{{ $d->status === 'active' ? 'Suspend' : 'Reactivate' }}</button></form>
                        <form method="POST" action="{{ route('identity.admin.branch-admins.revoke', $d) }}" class="inline" onsubmit="return confirm('Revoke this delegation?')">@csrf
                            <button class="text-xs text-red-600 hover:underline">Revoke</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="mt-3">{{ $delegations->links() }}</div>
    </div>
</x-app-layout>
