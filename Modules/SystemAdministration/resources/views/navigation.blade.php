<x-app-layout>
    <x-slot name="header">{{ __('Navigation Management') }}</x-slot>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">Hide, rename or reorder sidebar items. Hiding a menu is <strong>not</strong> authorization — route permissions still apply.</p>
        <form method="POST" action="{{ route('systemadmin.navigation.reset') }}" onsubmit="return confirm('Remove ALL navigation overrides?')">
            @csrf
            <button class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">Restore defaults</button>
        </form>
    </div>

    <div class="space-y-4">
        @foreach ($groups as $group => $items)
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-200"><h2 class="text-sm font-semibold text-gray-900">{{ $group }}</h2></div>
                <ul class="divide-y divide-gray-100">
                    @foreach ($items as $item)
                        @php $key = $item->module.':'.$item->label; $o = $overrides->get($key); @endphp
                        <li class="flex items-center gap-4 px-5 py-3 {{ $o?->is_hidden ? 'opacity-50' : '' }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $o?->label_override ?: $item->label }}
                                    @if ($o?->label_override)<span class="text-xs font-normal text-gray-400">(was: {{ $item->label }})</span>@endif
                                </p>
                                <p class="text-xs text-gray-400">{{ $item->module }} · order {{ $o?->order_override ?? $item->order }}@if ($item->children !== []) · {{ count($item->children) }} children @endif</p>
                            </div>
                            <form method="POST" action="{{ route('systemadmin.navigation.update', $key) }}" class="flex items-center gap-2">
                                @csrf @method('PUT')
                                <input type="text" name="label_override" value="{{ $o?->label_override }}" placeholder="Label"
                                       class="w-32 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <input type="number" name="order_override" value="{{ $o?->order_override }}" placeholder="Order" min="0" max="999"
                                       class="w-20 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label class="flex items-center gap-1.5 text-xs text-gray-500">
                                    <input type="hidden" name="is_hidden" value="0">
                                    <input type="checkbox" name="is_hidden" value="1" @checked($o?->is_hidden)
                                           class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"> Hidden
                                </label>
                                <button class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Save</button>
                            </form>
                        </li>
                        @foreach ($item->children as $child)
                            <li class="flex items-center gap-4 px-5 py-2.5 pl-12 bg-gray-50/50">
                                <p class="flex-1 text-xs text-gray-500">↳ {{ $child->label }} <span class="text-gray-300">({{ $child->module }})</span></p>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-app-layout>
