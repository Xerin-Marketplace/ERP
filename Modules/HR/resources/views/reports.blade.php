<x-app-layout>
    <x-slot name="header">{{ __('HR Reports') }}</x-slot>
    <form method="GET" class="mb-4 flex gap-3 items-end">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-sm border-gray-300 text-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-sm border-gray-300 text-sm">
        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Apply</button>
    </form>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Attendance</h3>
            <ul class="text-sm space-y-1">
                @foreach ($report['attendance'] as $k => $v)
                    <li class="flex justify-between"><span>{{ ucwords(str_replace('_',' ',$k)) }}</span><b>{{ $v }}</b></li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Leave</h3>
            <ul class="text-sm space-y-1">
                @foreach ($report['leave'] as $k => $v)
                    <li class="flex justify-between"><span>{{ ucwords(str_replace('_',' ',$k)) }}</span><b>{{ $v }}</b></li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Overtime</h3>
            <ul class="text-sm space-y-1">
                @foreach ($report['overtime'] as $k => $v)
                    <li class="flex justify-between"><span>{{ ucwords(str_replace('_',' ',$k)) }}</span><b>{{ $v }}</b></li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Recruitment</h3>
            <ul class="text-sm space-y-1">
                @foreach ($report['recruitment'] as $k => $v)
                    <li class="flex justify-between"><span>{{ ucwords(str_replace('_',' ',$k)) }}</span><b>{{ $v }}</b></li>
                @endforeach
            </ul>
        </div>
    </div>
</x-app-layout>
