<x-guest-layout>
    <div class="max-w-3xl mx-auto py-12 px-4">
        <h1 class="text-2xl font-bold text-gray-900">Access Requests</h1>
        <p class="mt-1 text-sm text-gray-500">Request system access, or track an existing request.</p>
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ([
                ['new_employee', 'New Employee', 'I am joining the company and need an account.'],
                ['existing_employee', 'Additional Access', 'I already work here and need more access.'],
                ['external', 'External / Contractor', 'Contractor, agent, or partner — time-limited access.'],
            ] as [$type, $title, $desc])
                <a href="{{ route('access.form', $type) }}" class="rounded-sm border border-gray-200 bg-white p-6 shadow-sm hover:border-xerin-400 transition">
                    <h2 class="font-semibold text-gray-900">{{ $title }}</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ $desc }}</p>
                </a>
            @endforeach
            <a href="{{ route('access.track') }}" class="rounded-sm border border-xerin-200 bg-xerin-50 p-6 hover:border-xerin-400 transition">
                <h2 class="font-semibold text-xerin-800">Track a Request</h2>
                <p class="mt-1 text-xs text-xerin-600">Check status with your reference &amp; token.</p>
            </a>
        </div>
    </div>
</x-guest-layout>
