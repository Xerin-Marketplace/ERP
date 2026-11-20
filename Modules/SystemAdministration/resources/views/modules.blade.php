<x-app-layout>
    <x-slot name="header">{{ __('Module Management') }}</x-slot>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Registered modules</h2>
            <p class="mt-0.5 text-xs text-gray-500">Disabling a module hides its routes and navigation — it never deletes data. Protected modules cannot be disabled.</p>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b border-gray-200">
                    <th class="px-5 py-3 font-medium">Module</th>
                    <th class="px-5 py-3 font-medium">Description</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($modules as $m)
                    <tr>
                        <td class="px-5 py-3.5 font-medium text-gray-900">
                            {{ $m['name'] }}
                            @if ($m['protected'])<span class="ms-1.5 text-[10px] font-semibold uppercase text-gray-400">protected</span>@endif
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $m['description'] }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $m['enabled'] ? 'text-green-700 bg-green-50' : 'text-gray-500 bg-gray-100' }} px-2 py-1 rounded-sm">
                                <span class="w-1.5 h-1.5 rounded-full {{ $m['enabled'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>{{ $m['enabled'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            @unless ($m['protected'])
                                <form method="POST" action="{{ route('systemadmin.modules.toggle', $m['name']) }}"
                                      onsubmit="return confirm('{{ $m['enabled'] ? 'Disable' : 'Enable' }} the {{ $m['name'] }} module?')">
                                    @csrf
                                    <button class="rounded-md px-3 py-1.5 text-xs font-medium {{ $m['enabled'] ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                        {{ $m['enabled'] ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
