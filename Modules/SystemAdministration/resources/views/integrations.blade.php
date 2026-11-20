<x-app-layout>
    <x-slot name="header">{{ __('Integration Management') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-3">
            @forelse ($integrations as $i)
                <div class="rounded-sm border border-gray-200 bg-white shadow-sm" x-data="{ open: false }">
                    <div class="flex items-center gap-4 px-5 py-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $i->name }} <span class="ms-1 font-mono text-xs font-normal text-gray-400">{{ $i->key }}</span></p>
                            <p class="text-xs text-gray-400">{{ $i->driver }} · {{ $i->is_enabled ? 'enabled' : 'disabled' }}
                                @if ($i->last_status) · last test: <span class="{{ $i->last_status === 'ok' ? 'text-green-600' : 'text-red-600' }}">{{ $i->last_status }}</span> {{ $i->last_tested_at?->diffForHumans() }}@endif
                            </p>
                        </div>
                        <button x-on:click="open = !open" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">Configure</button>
                        <form method="POST" action="{{ route('systemadmin.integrations.test', $i) }}">@csrf
                            <button class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">Test</button>
                        </form>
                        <form method="POST" action="{{ route('systemadmin.integrations.toggle', $i) }}">@csrf
                            <button class="rounded-md px-3 py-1.5 text-xs font-medium {{ $i->is_enabled ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">{{ $i->is_enabled ? 'Disable' : 'Enable' }}</button>
                        </form>
                    </div>
                    <div x-show="open" x-cloak class="border-t border-gray-100 px-5 py-4">
                        <form method="POST" action="{{ route('systemadmin.integrations.update', $i) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @csrf @method('PUT')
                            <div><label class="block text-xs font-medium text-gray-600">Display name</label>
                                <input name="name" type="text" value="{{ $i->name }}" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-600">Endpoint URL</label>
                                <input name="endpoint" type="url" value="{{ $i->configArray()['endpoint'] ?? '' }}" placeholder="https://…" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-600">Username / Client ID</label>
                                <input name="username" type="text" value="{{ $i->configArray()['username'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-600">Secret / API key</label>
                                <input name="secret" type="password" placeholder="{{ ($i->configArray()['secret'] ?? null) ? '•••••••• (stored)' : 'enter secret' }}" autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <p class="mt-1 text-[11px] text-gray-400">Encrypted at rest. Never displayed or logged.</p></div>
                            <div class="sm:col-span-2"><label class="block text-xs font-medium text-gray-600">Extra config (JSON)</label>
                                <input name="extra" type="text" value="{{ isset($i->configArray()['extra']) ? json_encode($i->configArray()['extra']) : '' }}" placeholder='{"region":"us-east"}' class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm"></div>
                            <input type="hidden" name="is_enabled" value="{{ $i->is_enabled ? 1 : 0 }}">
                            <div class="sm:col-span-2"><button class="rounded-md bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800">Save configuration</button></div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-sm border border-gray-200 bg-white p-10 text-center text-sm text-gray-400 shadow-sm">No integrations registered yet.</div>
            @endforelse
        </div>

        <form method="POST" action="{{ route('systemadmin.integrations.store') }}" class="rounded-sm border border-gray-200 bg-white shadow-sm self-start">
            @csrf
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Register integration</h2></div>
            <div class="p-5 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700">Key</label>
                    <input name="key" type="text" required placeholder="e.g. m-pesa" class="mt-1.5 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Name</label>
                    <input name="name" type="text" required placeholder="M-Pesa Payments" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Driver</label>
                    <select name="driver" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="smtp">Email (SMTP)</option><option value="sms">SMS</option><option value="payment">Payment gateway</option>
                        <option value="storage">Storage</option><option value="whatsapp">WhatsApp Business</option><option value="custom">Custom API</option>
                    </select></div>
            </div>
            <div class="border-t border-gray-100 px-5 py-4"><button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Register</button></div>
        </form>
    </div>
</x-app-layout>
