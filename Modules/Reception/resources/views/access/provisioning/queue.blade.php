<x-app-layout>
    <x-slot name="header">Provisioning Queue</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm"><tbody>
        @forelse ($requests as $r)
            <tr class="border-b border-gray-50">
                <td class="px-4 py-3 font-mono text-xs">{{ $r->reference }}</td>
                <td class="px-4 py-3">{{ $r->full_name }}<p class="text-xs text-gray-400">{{ $r->email }}</p></td>
                <td class="px-4 py-3 text-xs">{{ str_replace('_',' ',$r->type) }}</td>
                <td class="px-4 py-3"><span class="text-xs font-semibold">{{ str_replace('_',' ',ucfirst($r->status)) }}</span></td>
                <td class="px-4 py-3 text-xs">{{ $r->provisionedUser->email ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if ($r->status === 'approved')
                        <a href="{{ route('reception.access.provisioning.configure', $r) }}" class="text-xs font-semibold text-xerin-700">Configure &amp; Provision →</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">Queue empty.</td></tr>
        @endforelse
        </tbody></table>
    </div>
</x-app-layout>
