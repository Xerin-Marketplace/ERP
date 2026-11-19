<x-app-layout>
    <x-slot name="header">{{ __('SLA Policies') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Policy</th><th class="px-4 py-3 text-left">Applies to</th>
                    <th class="px-4 py-3 text-left">Response</th><th class="px-4 py-3 text-left">Resolution</th>
                    <th class="px-4 py-3 text-left">Hours</th><th class="px-4 py-3 text-left">Warn</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($policies as $p)
                        <tr><td class="px-4 py-3 font-medium">{{ $p->name }}</td>
                            <td class="px-4 py-3 text-xs">{{ $p->priority ? ucfirst($p->priority) : 'All' }}{{ $p->type ? ' / '.$p->type->name : '' }}</td>
                            <td class="px-4 py-3">{{ $p->response_minutes }}m</td>
                            <td class="px-4 py-3">{{ round($p->resolution_minutes/60, 1) }}h</td>
                            <td class="px-4 py-3 text-xs">{{ $p->business_hours_start }}:00–{{ $p->business_hours_end }}:00</td>
                            <td class="px-4 py-3 text-xs">{{ $p->warn_percent }}%</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-3">New Policy</h3>
            <form method="POST" action="{{ route('ticketing.admin.sla.store') }}" class="space-y-2">
                @csrf
                <input name="name" required placeholder="Name *" class="w-full rounded-sm border-gray-300 text-sm">
                <select name="priority" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Any priority</option>
                    @foreach (\Modules\Ticketing\Models\Ticket::PRIORITIES as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select>
                <select name="ticket_type_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Any type</option>
                    @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                <input name="response_minutes" type="number" required placeholder="Response minutes *" class="w-full rounded-sm border-gray-300 text-sm">
                <input name="resolution_minutes" type="number" required placeholder="Resolution minutes *" class="w-full rounded-sm border-gray-300 text-sm">
                <div class="flex gap-2">
                    <input name="business_hours_start" type="number" value="8" min="0" max="23" class="w-full rounded-sm border-gray-300 text-sm" title="Business hours start">
                    <input name="business_hours_end" type="number" value="17" min="1" max="24" class="w-full rounded-sm border-gray-300 text-sm" title="Business hours end">
                </div>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Save Policy</button>
            </form>
        </div>
    </div>
</x-app-layout>
