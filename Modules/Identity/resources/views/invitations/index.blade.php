<x-app-layout>
    <x-slot name="header">Pending Invitations</x-slot>

    <div class="rounded-sm border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-400">
                <tr><th class="px-5 py-3">User</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Expires</th><th class="px-5 py-3">Sent</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($invitations as $inv)
                    <tr>
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $inv->user?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $inv->user?->email }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $inv->expires_at?->diffForHumans() ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $inv->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('identity.invitations.revoke', $inv) }}" onsubmit="return confirm('Revoke this invitation?')">
                                @csrf
                                <button class="text-xs font-medium text-red-600 hover:text-red-800">Revoke</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No pending invitations.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 px-5 py-3">{{ $invitations->links() }}</div>
    </div>
</x-app-layout>
