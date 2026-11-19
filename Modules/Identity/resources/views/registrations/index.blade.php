<x-app-layout>
    <x-slot name="header">{{ __('Registration Applications') }}</x-slot>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
        @php
            $cards = [
                ['Total', $stats['total'], 'text-gray-900'],
                ['Pending', $stats['pending_review'], 'text-amber-600'],
                ['In review', $stats['under_review'], 'text-blue-600'],
                ['Info req.', $stats['information_requested'], 'text-purple-600'],
                ['Approved', $stats['approved'], 'text-green-600'],
                ['Rejected', $stats['rejected'], 'text-red-600'],
                ['Provisioning', $stats['provisioning'], 'text-indigo-600'],
                ['Activated', $stats['active'], 'text-gray-900'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $color])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, name, email"
                   class="w-56 rounded-md border-gray-300 text-sm shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">All</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->publicLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 text-sm shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 text-sm shadow-sm">
        </div>
        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
        <a href="{{ route('identity.registrations.index') }}" class="text-sm text-gray-500 hover:underline">Reset</a>
    </form>

    {{-- Table --}}
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Applicant</th>
                    <th class="px-4 py-3">Requested</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Reviewer</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $app->reference }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $app->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $app->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $app->requestedCompany?->legal_name ?? '—' }}
                            @if ($app->job_title)<p class="text-xs text-gray-400">{{ $app->job_title }}</p>@endif
                        </td>
                        <td class="px-4 py-3">
                            @php $cls = match($app->status->value) {
                                'submitted','pending_review' => 'bg-amber-100 text-amber-700',
                                'under_review' => 'bg-blue-100 text-blue-700',
                                'information_requested' => 'bg-purple-100 text-purple-700',
                                'approved','provisioning' => 'bg-green-100 text-green-700',
                                'rejected','cancelled' => 'bg-red-100 text-red-700',
                                'active' => 'bg-gray-200 text-gray-700',
                                default => 'bg-gray-100 text-gray-600',
                            }; @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $cls }}">{{ $app->status->publicLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->reviewer?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $app->submitted_at?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('identity.registrations.show', $app) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No applications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $applications->links() }}</div>
</x-app-layout>
