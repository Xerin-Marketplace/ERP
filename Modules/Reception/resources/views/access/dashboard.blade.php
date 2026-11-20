<x-app-layout>
    <x-slot name="header">Access Management</x-slot>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        @php $cards = [
            ['Total', $metrics['total'], 'gray'], ['New Today', $metrics['today'], 'blue'],
            ['Awaiting Reception', $metrics['pending_reception'], 'amber'],
            ['In Approvals', $metrics['in_approval'], 'xerin'],
            ['Provisioning', $metrics['provisioning'], 'purple'],
            ['Activated', $metrics['activated'], 'green'],
            ['Rejected', $metrics['rejected'], 'red'],
            ['Info Required', $metrics['info_required'], 'amber'],
            ['Overdue Tasks', $metrics['overdue'], 'red'],
        ]; @endphp
        @foreach ($cards as [$label, $value, $color])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-{{ $color }}-600">{{ $value }}</p>
            </div>
        @endforeach
    </div>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 p-4">
            <h3 class="text-sm font-semibold">Recent Requests</h3>
            <a href="{{ route('reception.access.requests.index') }}" class="text-xs font-semibold text-xerin-700">View all →</a>
        </div>
        <table class="w-full text-sm"><tbody>
        @foreach ($recent as $r)
            <tr class="border-b border-gray-50"><td class="px-4 py-2 font-mono text-xs">{{ $r->reference }}</td>
                <td class="px-4 py-2">{{ $r->full_name }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ str_replace('_',' ',$r->type) }}</td>
                <td class="px-4 py-2"><span class="text-xs font-semibold">{{ str_replace('_',' ',ucfirst($r->status)) }}</span></td>
                <td class="px-4 py-2 text-xs text-gray-400">{{ $r->submitted_at->diffForHumans() }}</td>
                <td class="px-4 py-2"><a href="{{ route('reception.access.requests.show', $r) }}" class="text-xs font-semibold text-xerin-700">Open</a></td></tr>
        @endforeach
        </tbody></table>
    </div>
</x-app-layout>
