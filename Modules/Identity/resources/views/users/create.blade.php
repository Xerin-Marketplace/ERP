<x-app-layout>
    <x-slot name="header">Create User & Send Invitation</x-slot>

    <div class="rounded-sm border border-gray-200 bg-white p-6">
        <p class="mb-5 text-sm text-gray-500">The user is created in <strong>pending</strong> status and receives an activation link by email. No password is set by the administrator.</p>
        <form method="POST" action="{{ route('identity.users.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <x-input-label for="name" value="Full name *" />
                <x-text-input id="name" name="name" class="block mt-1.5 w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" value="Email *" />
                <x-text-input id="email" name="email" type="email" class="block mt-1.5 w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input id="phone" name="phone" class="block mt-1.5 w-full" :value="old('phone')" />
            </div>
            <div>
                <x-input-label for="branch_id" value="Branch" />
                <select id="branch_id" name="branch_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">—</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('branch_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="role" value="Initial role" />
                <select id="role" name="role" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">—</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->name }}" @selected(old('role') === $r->name)>{{ $r->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-1" />
            </div>
            <div class="sm:col-span-2 mt-4 flex items-center gap-3">
                <x-primary-button>Create & send invitation</x-primary-button>
                <a href="{{ route('identity.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
