<x-app-layout>
    <x-slot name="header">Set Up Two-Factor Authentication</x-slot>
    <div class="max-w-lg rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-gray-600">Your role requires MFA. Add this account to your authenticator app
            (Google Authenticator, Authy, 1Password), then enter the 6-digit code.</p>
        <div class="mt-4 rounded-sm bg-gray-50 p-4">
            <p class="text-xs text-gray-500">Manual entry key</p>
            <p class="font-mono text-lg font-bold tracking-widest select-all">{{ $setup['secret'] }}</p>
            <p class="mt-2 break-all font-mono text-xs text-gray-400">{{ $setup['uri'] }}</p>
        </div>
        <form method="POST" action="{{ route('mfa.enroll.confirm') }}" class="mt-4 flex gap-2">
            @csrf
            <input name="code" maxlength="6" required placeholder="6-digit code" class="w-40 rounded-sm border-gray-300 font-mono text-sm">
            <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Enable MFA</button>
        </form>
        @error('code') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
</x-app-layout>
