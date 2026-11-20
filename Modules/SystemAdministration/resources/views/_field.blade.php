{{-- Renders a typed setting field. Expects: $key, $def, $value --}}
@php
    $id = 'f_' . md5($key);
    $locked = $def['locked'] ?? false;
    $name = "settings[{$key}]";
    $fieldError = $errors->getMessages()[$key][0] ?? null;
@endphp
<div>
    @if ($def['type'] === 'bool')
        <label class="flex items-start gap-3">
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value) @disabled($locked)
                   class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span>
                <span class="block text-sm font-medium text-gray-700">
                    {{ $def['label'] }}
                    @if ($locked)<span class="ms-1 text-[10px] font-semibold uppercase text-gray-400">locked</span>@endif
                </span>
                @if ($def['help'] ?? false)<span class="block text-xs text-gray-500">{{ $def['help'] }}</span>@endif
            </span>
        </label>
    @elseif ($def['type'] === 'enum')
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">{{ $def['label'] }}</label>
        <select id="{{ $id }}" name="{{ $name }}" @disabled($locked)
                class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($def['options'] as $opt => $label)
                <option value="{{ $opt }}" @selected((string) $value === (string) $opt)>{{ $label }}</option>
            @endforeach
        </select>
    @elseif ($def['type'] === 'multiselect')
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">{{ $def['label'] }}</label>
        <select id="{{ $id }}" name="{{ $name }}[]" multiple size="{{ count($def['options']) }}"
                class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($def['options'] as $opt => $label)
                <option value="{{ $opt }}" @selected(in_array($opt, (array) $value))>{{ $label }}</option>
            @endforeach
        </select>
    @elseif ($def['type'] === 'color')
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">{{ $def['label'] }}</label>
        <div class="mt-1.5 flex items-center gap-2">
            <input id="{{ $id }}" type="color" value="{{ $value }}" oninput="document.getElementById('{{ $id }}_hex').value = this.value"
                   class="h-9 w-12 rounded border border-gray-300 p-1">
            <input id="{{ $id }}_hex" name="{{ $name }}" type="text" value="{{ $value }}" maxlength="7" required
                   class="block w-28 rounded-md border-gray-300 text-sm font-mono shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    @elseif (in_array($def['type'], ['int', 'float']))
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">{{ $def['label'] }}</label>
        <input id="{{ $id }}" name="{{ $name }}" type="number" step="{{ $def['type'] === 'float' ? '0.01' : '1' }}" value="{{ $value }}" required @disabled($locked)
               class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    @else
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">{{ $def['label'] }}</label>
        <input id="{{ $id }}" name="{{ $name }}" type="text" value="{{ $value }}" @disabled($locked)
               class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    @endif
    @if ($def['type'] !== 'bool' && ($def['help'] ?? false))<p class="mt-1 text-xs text-gray-400">{{ $def['help'] }}</p>@endif
    @if ($fieldError)<p class="mt-1 text-xs text-red-600">{{ $fieldError }}</p>@endif
</div>
