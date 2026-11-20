<x-app-layout>
    <x-slot name="header">Permission Groups</x-slot>

    <div class="mb-4">
        <form method="GET"><input type="text" name="search" value="{{ request('search') }}" placeholder="Filter permissions…" class="w-72 rounded-sm border-gray-300 text-sm"></form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($groups as $group => $perms)
            <div class="rounded-sm border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-900">{{ ucfirst($group) }} <span class="ml-1 text-xs font-normal text-gray-400">({{ count($perms) }})</span></div>
                <ul class="divide-y divide-gray-50 px-4 py-1">
                    @foreach ($perms as $p)
                        <li class="py-1.5 font-mono text-xs text-gray-600">{{ $p }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-app-layout>
