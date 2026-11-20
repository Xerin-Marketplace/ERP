<x-app-layout>
    <x-slot name="header">{{ __('IT Team Availability') }}</x-slot>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
                <th class="px-4 py-2">Technician</th><th class="px-4 py-2">Team</th><th class="px-4 py-2">Availability</th>
                <th class="px-4 py-2">Active Tickets</th><th class="px-4 py-2">Last Update</th>
            </tr></thead>
            <tbody>
                @forelse ($technicians as $t)
                    @php $dot = ['available'=>'green','busy'=>'amber','on_break'=>'blue','on_leave'=>'purple','offline'=>'gray'][$t->availability] ?? 'gray'; @endphp
                    <tr class="border-b last:border-0">
                        <td class="px-4 py-2 font-medium">{{ $t->first_name }} {{ $t->last_name }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $t->team }}</td>
                        <td class="px-4 py-2"><span class="flex items-center gap-2 text-xs text-{{ $dot }}-600"><i class="w-2 h-2 rounded-full bg-{{ $dot }}-500"></i>{{ str_replace('_',' ',ucfirst($t->availability)) }}</span></td>
                        <td class="px-4 py-2 text-xs">{{ $t->active_tickets }}</td>
                        <td class="px-4 py-2 text-xs text-gray-400">{{ $t->availability_updated_at ? \Illuminate\Support\Carbon::parse($t->availability_updated_at)->diffForHumans() : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No IT team members configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
