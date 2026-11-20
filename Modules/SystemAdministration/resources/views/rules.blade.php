@php $old = old('settings', []); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('System Rules & Policies') }}</x-slot>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2">
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($groups->keys() as $g)
                    <a href="{{ route('systemadmin.rules', ['g' => $g]) }}"
                       class="rounded-md px-3 py-1.5 text-xs font-medium {{ $g === $selected ? 'bg-gray-900 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ str_replace('Rules — ', '', $g) }}</a>
                @endforeach
            </div>

            <form method="POST" action="{{ route('systemadmin.rules.update') }}">
                @csrf @method('PUT')
                @php $defs = $groups[$selected]; $vals = $values[$selected]; @endphp
                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">{{ $selected }}</h2>
                        <span class="text-xs text-gray-400">{{ $defs->count() }} rules</span>
                    </div>
                    <div class="p-5 space-y-5">
                        @foreach ($defs as $key => $def)
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    @include('systemadministration::_field', ['key' => $key, 'def' => $def, 'value' => $old[$key] ?? $vals[$key]])
                                </div>
                                <span class="mt-6 shrink-0 rounded-sm px-1.5 py-0.5 text-[10px] font-semibold uppercase
                                    {{ $def['risk'] === 'critical' ? 'bg-red-100 text-red-700' : ($def['risk'] === 'high' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">{{ $def['risk'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-gray-100 px-5 py-4">
                        <label for="reason" class="block text-sm font-medium text-gray-700">Change reason <span class="text-gray-400 font-normal">(audited)</span></label>
                        <input id="reason" name="reason" type="text" maxlength="150" placeholder="Why are these rules changing?"
                               class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <button type="submit" class="mt-5 rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">Publish rule changes</button>
            </form>
        </div>

        <!-- Change history -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm self-start">
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Recent changes</h2></div>
            <ul class="divide-y divide-gray-100 max-h-[36rem] overflow-y-auto">
                @forelse ($history as $v)
                    <li class="px-5 py-3">
                        <p class="font-mono text-xs text-gray-700">{{ $v->key }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            <span class="text-gray-400">{{ $v->old_value ?? '∅' }}</span> → <span class="font-medium">{{ $v->new_value }}</span>
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $v->changedBy?->name ?? 'system' }} · {{ $v->created_at->diffForHumans() }}@if ($v->reason) · {{ $v->reason }}@endif</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No rule changes yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
