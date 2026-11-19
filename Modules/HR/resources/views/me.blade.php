<x-app-layout>
    <x-slot name="header">{{ __('My HR') }} — {{ $employee->full_name }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Today's Attendance</h3>
            <p class="text-xs text-gray-500 mb-3">{{ now()->format('l, M j') }} · {{ $today->shift?->name ?? 'No shift' }}</p>
            <div class="flex gap-2 flex-wrap">
                @if (! $today->clock_in_at)
                    <form method="POST" action="{{ route('hr.attendance.clock-in') }}">@csrf
                        <button class="rounded-sm bg-green-600 px-4 py-2 text-sm font-semibold text-white">Clock In</button></form>
                @elseif (! $today->clock_out_at)
                    <form method="POST" action="{{ route('hr.attendance.clock-out') }}">@csrf
                        <button class="rounded-sm bg-red-600 px-4 py-2 text-sm font-semibold text-white">Clock Out</button></form>
                @else
                    <p class="text-sm text-green-600 font-semibold">Day complete ✓</p>
                @endif
            </div>
            <dl class="mt-3 text-xs space-y-1">
                <div class="flex justify-between"><dt>In</dt><dd>{{ $today->clock_in_at?->format('H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Out</dt><dd>{{ $today->clock_out_at?->format('H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Status</dt><dd>{{ ucfirst($today->status) }}</dd></div>
            </dl>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Leave Balances ({{ now()->year }})</h3>
            <ul class="text-sm space-y-1.5">
                @forelse ($leave_balances as $b)
                    <li class="flex justify-between"><span>{{ $b->type->name }}</span>
                        <span><b>{{ $b->available }}</b><span class="text-xs text-gray-400">/{{ $b->entitled + $b->carried_forward }}d</span></span></li>
                @empty <li class="text-gray-400">No balances.</li> @endforelse
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">This Month</h3>
            <ul class="text-sm space-y-1">
                <li class="flex justify-between"><span>Days present</span><b>{{ $attendance_month['present'] }}</b></li>
                <li class="flex justify-between"><span>Late</span><b>{{ $attendance_month['late'] }}</b></li>
                <li class="flex justify-between"><span>Hours worked</span><b>{{ round($attendance_month['worked_minutes']/60, 1) }}</b></li>
                <li class="flex justify-between"><span>Pending leave</span><b>{{ $pending_leave }}</b></li>
                <li class="flex justify-between"><span>Pending overtime</span><b>{{ $pending_overtime }}</b></li>
            </ul>
            @if ($shift)
                <p class="mt-3 text-xs text-gray-500">Shift: {{ $shift->name }} ({{ $shift->start_time->format('H:i') }}–{{ $shift->end_time->format('H:i') }})</p>
            @endif
        </div>
    </div>
</x-app-layout>
