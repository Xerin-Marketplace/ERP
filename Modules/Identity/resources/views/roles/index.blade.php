<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ __('Roles & Access') }}</span>
            @can('role.create')
                <a href="{{ route('identity.roles.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">New role</a>
            @endcan
        </div>
    </x-slot>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Users</th>
                    <th class="px-4 py-3">Permissions</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($roles as $role)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $role->name }}
                                @if (in_array($role->name, $protected))
                                    <span class="ml-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">protected</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-md">
                            <span class="line-clamp-2 text-xs">{{ $role->permissions->pluck('name')->join(', ') ?: '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @unless (in_array($role->name, $protected))
                                @can('role.update')
                                    <a href="{{ route('identity.roles.edit', $role) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Edit</a>
                                @endcan
                                @can('role.create')
                                    <form method="POST" action="{{ route('identity.roles.clone', $role) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $role->name }}-copy">
                                        <button class="text-sm font-medium text-gray-600 hover:text-gray-800">Clone</button>
                                    </form>
                                @endcan
                                @can('role.delete')
                                    @if ($role->users_count === 0)
                                        <form method="POST" action="{{ route('identity.roles.destroy', $role) }}" class="inline"
                                              onsubmit="return confirm('Delete role {{ $role->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                                        </form>
                                    @endif
                                @endcan
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
