<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Agents & Brokers</h2></x-slot>
    <form method="post" action="{{ route('sales.partners.store') }}" class="bg-white rounded-lg shadow p-4 mb-4 grid md:grid-cols-5 gap-2">@csrf
        <select name="type" class="border rounded px-2 py-1"><option>agent</option><option>broker</option></select>
        <input name="name" required placeholder="Name" class="border rounded px-2 py-1">
        <input name="phone" placeholder="Phone" class="border rounded px-2 py-1">
        <input name="email" placeholder="Email" class="border rounded px-2 py-1">
        <input name="registration_ref" placeholder="Registration ref" class="border rounded px-2 py-1">
        <button class="bg-blue-600 text-white rounded px-3 py-1">Register</button>
    </form>
    <div class="mb-2 text-sm"><a href="?type=agent" class="mr-3">Agents</a><a href="?type=broker">Brokers</a></div>
    <table class="w-full bg-white rounded-lg shadow text-sm">
        <tr class="text-left border-b"><th class="p-2">Ref</th><th>Type</th><th>Name</th><th>Contact</th><th>Status</th></tr>
        @foreach($partners as $p)<tr class="border-t"><td class="p-2 font-mono"><a class="text-blue-600" href="{{ route('sales.partners.show',$p) }}">{{ $p->reference }}</a></td>
        <td>{{ $p->type }}</td><td>{{ $p->name }}</td><td class="text-xs">{{ $p->phone }} {{ $p->email }}</td>
        <td><span class="px-2 py-0.5 rounded text-xs bg-{{ $p->status==='active'?'green':'gray' }}-100">{{ $p->status }}</span></td></tr>@endforeach
    </table>
</x-app-layout>
