<x-app-layout>
    <x-slot name="header">{{ $role ? "Edit role: {$role->name}" : 'Create role' }}</x-slot>

    <form method="POST" action="{{ $role ? route('identity.roles.update', $role) : route('identity.roles.store') }}"
          x-data="{ selected: @js(old('permissions', $role?->permissions->pluck('name')->all() ?? [])) }">
        @csrf
        @if ($role) @method('PUT') @endif

        @unless ($role)
            <div class="mb-5 max-w-sm">
                <x-input-label for="name" :value="__('Role name (lowercase-hyphen)')" />
                <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name')" required placeholder="e.g. finance-officer" />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
        @endunless

        <div class="space-y-4">
            @foreach ($permissionGroups as $group => $perms)
                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900 capitalize">{{ $group }}</h3>
                        <label class="text-xs text-indigo-600 cursor-pointer"
                               x-on:click="selected = [...new Set([...selected, ...@js($perms)])]">select all</label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1 p-3">
                        @foreach ($perms as $perm)
                            <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50">
                                <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                                       x-model="selected"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-gray-700">{{ $perm }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">
                {{ $role ? 'Save permissions' : 'Create role' }}
            </button>
            <a href="{{ route('identity.roles.index') }}" class="text-sm text-gray-500 self-center hover:underline">Cancel</a>
        </div>
    </form>
</x-app-layout>
