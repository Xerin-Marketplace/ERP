<x-app-layout>
    <x-slot name="header">Provision — {{ $request->reference }}</x-slot>
    <div class="max-w-2xl rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="font-semibold">{{ $request->full_name }}</h3>
        <p class="text-xs text-gray-500">{{ $request->email }} · {{ str_replace('_',' ',$request->type) }}
            @if ($request->expiry_date) · expires {{ $request->expiry_date->format('d M Y') }} @endif</p>
        @if ($request->type === 'external' && $request->sponsor)
            <p class="text-xs text-gray-500 mt-1">Sponsor: {{ $request->sponsor->name }} · Org: {{ $request->organization }}</p>
        @endif
        <form method="POST" action="{{ route('reception.access.provisioning.provision', $request) }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600">Roles to assign (within your delegation scope)</label>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($assignableRoles as $role)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="rounded-sm"> {{ $role->name }}
                        </label>
                    @endforeach
                </div>
                @error('roles.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            @if ($request->type === 'external')
            <div><label class="block text-xs font-semibold text-gray-600">External category</label>
                <select name="category" class="mt-1 rounded-sm border-gray-300 text-sm">
                    @foreach (['contractor','agent','visitor','partner'] as $c) <option>{{ $c }}</option> @endforeach
                </select></div>
            @endif
            <label class="flex items-start gap-2 text-xs text-gray-600">
                <input type="checkbox" name="confirm" value="1" required class="mt-0.5 rounded-sm">
                I confirm the requested access matches the approved scope, and I am authorized to grant it.
            </label>
            @error('confirm') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button class="rounded-sm bg-xerin-700 px-5 py-2 text-sm font-semibold text-white">Provision Account</button>
        </form>
    </div>
</x-app-layout>
