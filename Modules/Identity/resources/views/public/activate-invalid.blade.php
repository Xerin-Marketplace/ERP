<x-guest-layout>
    <div class="py-8 text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        </span>
        <h1 class="mt-4 text-xl font-bold text-gray-900">Activation link unavailable</h1>
        <p class="mt-2 text-sm text-gray-500">{{ $message }}</p>
        <a href="{{ route('login') }}" class="mt-6 inline-block rounded-md bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">Back to sign in</a>
    </div>
</x-guest-layout>
