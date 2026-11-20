<x-app-layout>
    <x-slot name="header">{{ __('Maintenance Center') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Read-only mode -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-900">Read-only mode</h2>
                <p class="mt-0.5 text-xs text-gray-500">Blocks all mutating requests (POST/PUT/DELETE) for non-privileged users. Users with maintenance permission keep full access — a built-in recovery path.</p>
            </div>
            <div class="flex items-center justify-between p-5">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded-sm {{ $readOnly ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $readOnly ? 'bg-amber-500' : 'bg-gray-400' }}"></span>{{ $readOnly ? 'ACTIVE' : 'Off' }}
                </span>
                <form method="POST" action="{{ route('systemadmin.maintenance.readonly') }}" onsubmit="return confirm('{{ $readOnly ? 'Disable' : 'Enable' }} global read-only mode?')">
                    @csrf
                    <button class="rounded-md px-4 py-2 text-sm font-medium {{ $readOnly ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-amber-600 text-white hover:bg-amber-500' }}">
                        {{ $readOnly ? 'Disable' : 'Enable read-only' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Laravel maintenance mode -->
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-900">Maintenance mode (artisan down)</h2>
                <p class="mt-0.5 text-xs text-gray-500">Takes the whole application offline with a 503 page. A secret bypass URL is generated for administrators — keep it safe.</p>
            </div>
            <div class="p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded-sm {{ $down ? 'bg-red-100 text-red-700' : 'bg-green-50 text-green-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $down ? 'bg-red-500' : 'bg-green-500' }}"></span>{{ $down ? 'DOWN' : 'UP' }}
                    </span>
                    @if ($down)
                        <form method="POST" action="{{ route('systemadmin.maintenance.up') }}">@csrf
                            <button class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-500">Bring application UP</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('systemadmin.maintenance.down') }}" onsubmit="return confirm('Take the application offline? A secret bypass URL will be generated.')">@csrf
                            <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Take DOWN for maintenance</button>
                        </form>
                    @endif
                </div>
                @if ($bypassSecret)
                    <div class="rounded-md bg-amber-50 border border-amber-200 p-3">
                        <p class="text-xs font-semibold text-amber-800">Admin bypass URL (valid 1 hour):</p>
                        <code class="mt-1 block text-xs text-amber-700 break-all">{{ url('/'.$bypassSecret) }}</code>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
