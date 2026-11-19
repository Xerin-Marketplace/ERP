<x-guest-layout>
    <div class="mb-8 text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
            </svg>
        </span>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Track your application</h1>
        <p class="mt-1 text-sm text-gray-500">Enter your reference number and the email used to apply.</p>
    </div>

    @if (session('reference'))
        <div class="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            Application received. Your reference: <strong>{{ session('reference') }}</strong> — keep it safe.
        </div>
    @endif

    <form method="POST" action="{{ route('register.track.lookup') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="reference" :value="__('Application reference')" />
            <x-text-input id="reference" name="reference" type="text" class="mt-1.5 block w-full uppercase" :value="old('reference', $reference)" required placeholder="XERIN-REG-2026-000001" />
            <x-input-error :messages="$errors->get('reference')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="email" :value="__('Email used to apply')" />
            <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email')" required placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <button type="submit" class="w-full rounded-md bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 mt-6">
            Check status
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm">
        <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Submit a new application</a>
    </div>
</x-guest-layout>
