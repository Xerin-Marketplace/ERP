<x-app-layout>
    <x-slot name="header">{{ __('Reception Workspace') }}</x-slot>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        @php $cards = [
            ['Employees', $kpis['totalEmployees'], 'reception.directory', 'gray'],
            ['Present Today', $kpis['present'], 'reception.attendance.index', 'green'],
            ['Late Arrivals', $kpis['late'], 'reception.attendance.index', 'amber'],
            ['Checked Out', $kpis['checkedOut'], 'reception.attendance.index', 'blue'],
            ['Absent', $kpis['confirmedAbsent'], 'reception.attendance.index', 'red'],
            ['Unrecorded', $kpis['unrecorded'], 'reception.attendance.index', 'gray'],
            ['Open Tickets', $kpis['openTickets'], 'reception.tickets.index', 'amber'],
            ['Resolved Today', $kpis['resolvedToday'], 'reception.tickets.index', 'green'],
        ]; @endphp
        @foreach ($cards as [$label, $value, $route, $color])
            <a href="{{ route($route) }}" class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm hover:border-xerin-300 transition">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-{{ $color }}-600">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Attendance trend --}}
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">Attendance — Last 7 Days</h3>
                <a href="{{ route('reception.attendance.index') }}" class="text-xs font-semibold text-xerin-700">Full view →</a>
            </div>
            <div class="space-y-2">
                @php $max = max(1, max(array_map('max', $trend['series']))); @endphp
                @foreach ($trend['labels'] as $i => $day)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-16 text-gray-500">{{ \Illuminate\Support\Carbon::parse($day)->format('D d') }}</span>
                        <div class="flex-1 flex h-4 rounded-sm overflow-hidden bg-gray-100">
                            <div class="bg-green-500" style="width:{{ $trend['series']['present'][$i] / $max * 100 }}%" title="Present {{ $trend['series']['present'][$i] }}"></div>
                            <div class="bg-amber-400" style="width:{{ $trend['series']['late'][$i] / $max * 100 }}%" title="Late {{ $trend['series']['late'][$i] }}"></div>
                            <div class="bg-red-400" style="width:{{ $trend['series']['absent'][$i] / $max * 100 }}%" title="Absent {{ $trend['series']['absent'][$i] }}"></div>
                        </div>
                        <span class="w-8 text-right text-gray-400">{{ $trend['series']['present'][$i] + $trend['series']['late'][$i] + $trend['series']['absent'][$i] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex gap-4 mt-3 text-xs text-gray-500">
                <span><i class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 mr-1"></i>Present</span>
                <span><i class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400 mr-1"></i>Late</span>
                <span><i class="inline-block w-2.5 h-2.5 rounded-full bg-red-400 mr-1"></i>Absent</span>
            </div>
        </div>

        {{-- Technicians --}}
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">IT Technicians</h3>
                @can('reception.it_availability.view')
                    <a href="{{ route('reception.technicians') }}" class="text-xs font-semibold text-xerin-700">All →</a>
                @endcan
            </div>
            @forelse ($technicians->take(6) as $t)
                @php $dot = ['available'=>'green','busy'=>'amber','on_break'=>'blue','on_leave'=>'purple','offline'=>'gray'][$t->availability] ?? 'gray'; @endphp
                <div class="flex items-center justify-between py-1.5 border-b last:border-0 text-sm">
                    <span>{{ $t->first_name }} {{ $t->last_name }}</span>
                    <span class="flex items-center gap-2 text-xs">
                        <span class="text-gray-400">{{ $t->active_tickets }} tickets</span>
                        <i class="w-2 h-2 rounded-full bg-{{ $dot }}-500"></i>
                        <span class="text-{{ $dot }}-600">{{ str_replace('_',' ',ucfirst($t->availability)) }}</span>
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400">No IT team members configured for this branch.</p>
            @endforelse
        </div>

        {{-- Today's attendance --}}
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">Today's Attendance</h3>
                <a href="{{ route('reception.attendance.index') }}" class="text-xs font-semibold text-xerin-700">All records →</a>
            </div>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b">
                    <th class="py-1">Employee</th><th>Dept</th><th>Clock In</th><th>Clock Out</th><th>Status</th>
                </tr></thead>
                <tbody>
                    @forelse ($attendance as $r)
                        <tr class="border-b last:border-0">
                            <td class="py-1.5">{{ $r->employee?->full_name }} <span class="text-xs text-gray-400">{{ $r->employee?->employee_number }}</span></td>
                            <td class="text-xs text-gray-500">{{ $r->employee?->department?->name ?? '—' }}</td>
                            <td class="text-xs">{{ $r->clock_in_at?->format('H:i') ?? '—' }}</td>
                            <td class="text-xs">{{ $r->clock_out_at?->format('H:i') ?? '—' }}</td>
                            <td><span class="text-xs rounded-full px-2 py-0.5 {{ $r->status==='late' ? 'bg-amber-100 text-amber-700' : ($r->status==='absent' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700') }}">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3 text-sm text-gray-400">No attendance records today.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tickets --}}
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">IT Tickets</h3>
                @can('reception.tickets.create')
                    <a href="{{ route('reception.tickets.create') }}" class="text-xs font-semibold text-xerin-700">+ New</a>
                @endcan
            </div>
            @forelse ($tickets->take(6) as $t)
                <a href="{{ route('reception.tickets.show', $t) }}" class="block py-2 border-b last:border-0 hover:bg-gray-50">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ $t->ticket_number }}</span>
                        <span class="text-xs rounded-full px-2 py-0.5 {{ in_array($t->status,['resolved','closed']) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 truncate">{{ $t->subject }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No tickets yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
