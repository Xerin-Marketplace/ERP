<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ $application->reference }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $application->full_name }} · {{ $application->email }}</p>
    </div>

    <div class="rounded-md bg-indigo-50 border border-indigo-200 px-4 py-3 mb-6 text-center">
        <p class="text-xs font-medium uppercase tracking-wider text-indigo-500">Current status</p>
        <p class="mt-1 text-lg font-bold text-indigo-900">{{ $application->status->publicLabel() }}</p>
        @if ($application->status->value === 'information_requested' && $application->applicant_message)
            <p class="mt-2 text-sm text-indigo-800">{{ $application->applicant_message }}</p>
        @endif
    </div>

    <ol class="relative border-l border-gray-200 space-y-5 ml-2">
        @foreach (array_reverse($timeline) as $step)
            <li class="ml-5">
                <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-indigo-500 ring-4 ring-indigo-100"></span>
                <p class="text-sm font-medium text-gray-900">{{ $step['status'] }}</p>
                <p class="text-xs text-gray-400">{{ $step['at']->format('d M Y, H:i') }}</p>
            </li>
        @endforeach
    </ol>

    <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm space-y-2">
        <p class="text-gray-500"><a href="{{ route('register.track') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Check another application</a></p>
        <p class="text-gray-500"><a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</a></p>
    </div>
</x-guest-layout>
