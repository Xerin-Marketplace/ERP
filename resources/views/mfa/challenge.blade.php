<x-guest-layout>
    <div class="max-w-sm mx-auto py-16 px-4">
        <h1 class="text-xl font-bold">Two-Factor Authentication</h1>
        <p class="mt-1 text-sm text-gray-500">Enter the 6-digit code from your authenticator app, or a recovery code.</p>
        <form method="POST" action="{{ route('mfa.verify') }}" class="mt-6 space-y-4">
            @csrf
            <input name="code" maxlength="12" required autofocus placeholder="000000" class="w-full rounded-sm border-gray-300 font-mono text-center text-lg tracking-widest">
            @error('code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button class="w-full rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Verify</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf
            <button class="text-xs text-gray-500 underline">Sign out instead</button></form>
    </div>
</x-guest-layout>
