<x-app-layout>
    <x-slot name="header">{{ __('Feature Flags') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm self-start">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Flags</h2></div>
            <ul class="divide-y divide-gray-100">
                @forelse ($flags as $flag)
                    <li class="px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $flag->label }} <span class="ms-1 font-mono text-xs font-normal text-gray-400">{{ $flag->key }}</span></p>
                                @if ($flag->description)<p class="mt-0.5 text-xs text-gray-500">{{ $flag->description }}</p>@endif
                                <p class="mt-0.5 text-xs text-gray-400">
                                    @if ($flag->starts_at || $flag->ends_at)Window: {{ $flag->starts_at?->format('Y-m-d') ?? '…' }} → {{ $flag->ends_at?->format('Y-m-d') ?? '…' }} · @endif
                                    Updated by {{ $flag->updatedBy?->name ?? 'system' }} {{ $flag->updated_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('systemadmin.flags.update', $flag) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="label" value="{{ $flag->label }}">
                                    <input type="hidden" name="description" value="{{ $flag->description }}">
                                    <input type="hidden" name="starts_at" value="{{ $flag->starts_at }}">
                                    <input type="hidden" name="ends_at" value="{{ $flag->ends_at }}">
                                    <input type="hidden" name="is_enabled" value="{{ $flag->is_enabled ? 0 : 1 }}">
                                    <button class="rounded-md px-3 py-1.5 text-xs font-medium {{ $flag->is_enabled ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ $flag->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('systemadmin.flags.destroy', $flag) }}" onsubmit="return confirm('Delete flag {{ $flag->key }}?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-md bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">Delete</button>
                                </form>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-gray-400">No feature flags defined.</li>
                @endforelse
            </ul>
        </div>

        <form method="POST" action="{{ route('systemadmin.flags.store') }}" class="rounded-sm border border-gray-200 bg-white shadow-sm self-start">
            @csrf
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">New flag</h2></div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Key</label>
                    <input name="key" type="text" required placeholder="e.g. payroll_beta" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm font-mono shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('key')" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Label</label>
                    <input name="label" type="text" required class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <input name="description" type="text" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Starts</label>
                        <input name="starts_at" type="datetime-local" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ends</label>
                        <input name="ends_at" type="datetime-local" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_enabled" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"> Enabled immediately
                </label>
            </div>
            <div class="border-t border-gray-100 px-5 py-4">
                <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Create flag</button>
            </div>
        </form>
    </div>
</x-app-layout>
