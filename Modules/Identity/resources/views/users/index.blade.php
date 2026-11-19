<x-app-layout>
    <x-slot name="header">{{ __('User Management') }}</x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email"
                   class="w-56 rounded-md border-gray-300 text-sm shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach (['pending','active','suspended','deactivated'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Role</label>
            <select name="role" class="rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach ($roles as $r)
                    <option value="{{ $r->name }}" @selected(request('role') === $r->name)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
    </form>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Employee no.</th>
                    <th class="px-4 py-3">Branch</th>
                    <th class="px-4 py-3">Roles</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $u)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $u->name }}</p>
                            <p class="text-xs text-gray-500">{{ $u->email }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $u->profile?->employee_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $u->branch?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @foreach ($u->getRoleNames() as $role)
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $role }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            @php $cls = match($u->status?->value) {
                                'active' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'suspended' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-200 text-gray-600',
                            }; @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $cls }}">{{ ucfirst($u->status?->value ?? 'pending') }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $u->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('identity.users.show', $u) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
