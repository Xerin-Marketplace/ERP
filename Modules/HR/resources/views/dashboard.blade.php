<x-app-layout>
    <x-slot name="header">{{ __('HR Dashboard') }}</x-slot>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @foreach ([['Total Employees', $metrics['employees']['total']], ['Active', $metrics['employees']['active']], ['Probation', $metrics['employees']['probation']], ['Open Vacancies', $metrics['recruitment']['open_vacancies']]] as [$l, $v])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">{{ $l }}</p><p class="mt-1 text-2xl font-bold">{{ $v }}</p></div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Today's Attendance</h3>
            <ul class="text-sm space-y-1">
                <li class="flex justify-between"><span>Present</span><b class="text-green-600">{{ $metrics['attendance_today']['present'] }}</b></li>
                <li class="flex justify-between"><span>Late</span><b class="text-amber-600">{{ $metrics['attendance_today']['late'] }}</b></li>
                <li class="flex justify-between"><span>Absent</span><b class="text-red-600">{{ $metrics['attendance_today']['absent'] }}</b></li>
                <li class="flex justify-between"><span>On leave</span><b>{{ $metrics['attendance_today']['on_leave'] }}</b></li>
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Pending Queues</h3>
            <ul class="text-sm space-y-1">
                <li class="flex justify-between"><span>Leave requests</span><b>{{ $metrics['leave']['pending'] }}</b></li>
                <li class="flex justify-between"><span>Overtime</span><b>{{ $metrics['overtime_pending'] }}</b></li>
                <li class="flex justify-between"><span>Interviews</span><b>{{ $metrics['recruitment']['interviews_pending'] }}</b></li>
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Announcements</h3>
            <ul class="text-sm space-y-1">
                @forelse ($announcements as $a)
                    <li><b>{{ $a->title }}</b><p class="text-xs text-gray-400">{{ Str::limit($a->body, 80) }}</p></li>
                @empty <li class="text-gray-400">No announcements.</li> @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
