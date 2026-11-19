<x-guest-layout>
    <div class="text-center">
        <!-- Icon -->
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50">
            <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488a2.25 2.25 0 01-2.178 0L5.433 11.89A2.25 2.25 0 014.25 9.906V9m17.5 0A2.25 2.25 0 0019.5 6.75h-15a2.25 2.25 0 00-2.25 2.25m17.5 0v9.75A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V9" />
            </svg>
        </div>

        <h1 class="mt-6 text-2xl font-semibold text-gray-900">{{ __('Verify your email') }}</h1>
        <p class="mt-3 text-sm leading-6 text-gray-500">
            {{ __('We\'ve sent an email to') }}
            <span class="font-medium text-gray-900">{{ auth()->user()->email }}</span>
            {{ __('to verify your email address and activate your account.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-700">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
        @csrf
        <x-primary-button class="w-full justify-center">
            {{ __('Resend verification email') }}
        </x-primary-button>
    </form>

    <div class="mt-6 pt-6 border-t border-gray-200 text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline">
                {{ __('Not you? Log out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
