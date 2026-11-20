<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Set a new password</h2>
        <p class="mt-1 text-sm text-gray-500">Your administrator issued a temporary password. Choose a new one to continue — you'll use it from now on.</p>
    </div>
    <form method="POST" action="{{ route('password.force.store') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="password" value="New password" />
            <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
            <p class="mt-1 text-xs text-gray-400">Min 8 characters, mixed case + a number.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
        </div>
        <div class="flex items-center justify-between">
            <form></form>
            <x-primary-button>Save password &amp; continue</x-primary-button>
        </div>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf<button class="text-xs text-gray-400 hover:text-gray-600">Sign out instead</button></form>
</x-guest-layout>
