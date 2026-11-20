<x-app-layout>
    <x-slot name="header">Clearing Agents</x-slot>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Add Agent</h3>
            <form method="POST" action="{{ route('arrival.agents.store') }}" class="space-y-3">@csrf
                <input name="name" required placeholder="Agent name" class="w-full rounded border-gray-300 text-sm">
                <input name="license_ref" placeholder="License ref" class="w-full rounded border-gray-300 text-sm">
                <input name="contact_name" placeholder="Contact" class="w-full rounded border-gray-300 text-sm">
                <input name="contact_phone" placeholder="Phone" class="w-full rounded border-gray-300 text-sm">
                <input name="contact_email" placeholder="Email" class="w-full rounded border-gray-300 text-sm">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Add</button></form>
        </div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Agent</th><th>License</th><th>Contact</th><th>Status</th></tr></thead>
            <tbody class="divide-y">@forelse($agents as $ag)
                <tr><td class="p-2 font-medium">{{ $ag->name }}</td><td>{{ $ag->license_ref ?? '—' }}</td><td>{{ $ag->contact_name }} {{ $ag->contact_phone }}</td><td><span class="text-xs px-2 rounded bg-green-100 text-green-700">{{ $ag->status }}</span></td></tr>
                @empty<tr><td colspan="4" class="p-4 text-center text-gray-400">No agents.</td></tr>@endforelse
            </tbody></table></div>
    </div>
</x-app-layout>
