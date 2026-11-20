<x-app-layout>
    <x-slot name="header">Permission Matrix</x-slot>

    <div class="overflow-x-auto rounded-sm border border-gray-200 bg-white">
        <table class="text-xs">
            <thead class="border-b border-gray-100 text-left uppercase tracking-wider text-gray-400">
                <tr>
                    <th class="sticky left-0 bg-white px-4 py-3">Permission</th>
                    @foreach ($roles as $role)
                        <th class="px-3 py-3 font-medium">{{ $role->name }}@if(in_array($role->name, $protected)) <span class="text-amber-500">🔒</span>@endif</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group => $perms)
                    <tr class="bg-gray-50"><td colspan="{{ $roles->count() + 1 }}" class="px-4 py-2 font-semibold text-gray-700">{{ ucfirst($group) }}</td></tr>
                    @foreach ($perms as $perm)
                        <tr class="border-t border-gray-50">
                            <td class="sticky left-0 bg-white px-4 py-1.5 font-mono text-gray-600">{{ $perm }}</td>
                            @foreach ($roles as $role)
                                <td class="px-3 py-1.5 text-center">
                                    @if (in_array($role->name, $protected))
                                        <span class="text-gray-300" title="Protected — managed by seeder">●</span>
                                    @else
                                        @php $has = $role->permissions->contains('name', $perm); @endphp
                                        <input type="checkbox" form="matrix-{{ $role->id }}" name="permissions[]" value="{{ $perm }}" @checked($has) class="rounded border-gray-300 text-indigo-600">
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    @can('permissions.manage')
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($roles->reject(fn ($r) => in_array($r->name, $protected)) as $role)
                <form id="matrix-{{ $role->id }}" method="POST" action="{{ route('identity.permissions.matrix.update', $role) }}">
                    @csrf @method('PUT')
                    <button class="rounded-sm border border-indigo-300 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Save {{ $role->name }}</button>
                </form>
            @endforeach
        </div>
    @endcan
</x-app-layout>
