<x-app-layout>
    <x-slot name="header">Complaints Register</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Customer</th><th>Category</th><th>Status</th><th>Move</th></tr></thead>
        <tbody class="divide-y">@foreach($complaints as $c)
            <tr><td class="p-2 font-mono">{{ $c->reference }}</td><td>{{ $c->customer?->name }}</td><td class="capitalize">{{ str_replace('_',' ',$c->category) }}</td>
                <td><span class="text-xs px-2 rounded bg-amber-100 text-amber-700">{{ str_replace('_',' ',$c->status) }}</span></td>
                <td><form method="POST" action="{{ route('desk.complaints.transition', $c) }}" class="flex gap-1">@csrf
                    <select name="to" class="rounded border-gray-300 text-xs">@foreach(\Modules\Portal\Models\Complaint::TRANSITIONS[$c->status] ?? [] as $to)<option value="{{ $to }}">{{ str_replace('_',' ',$to) }}</option>@endforeach</select>
                    <button class="px-2 py-0.5 bg-indigo-600 text-white rounded text-xs">Go</button></form></td></tr>
            @endforeach</tbody></table>{{ $complaints->links() }}</div>
</x-app-layout>
