<x-app-layout>
    <x-slot name="header">{{ __('Chart of Accounts') }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr>
                <th class="p-3">Code</th><th class="p-3">Name</th><th class="p-3">Type</th>
                <th class="p-3">Parent</th><th class="p-3">Normal</th><th class="p-3">Postable</th><th class="p-3">Status</th>
            </tr></thead>
            <tbody>
            @foreach ($accounts as $a)
                <tr class="border-t">
                    <td class="p-3 font-mono">{{ $a->code }}</td>
                    <td class="p-3">{{ $a->parent_id ? '— ' : '' }}{{ $a->name }}</td>
                    <td class="p-3"><span class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ $a->type }}</span></td>
                    <td class="p-3 text-gray-500">{{ $a->parent?->code }}</td>
                    <td class="p-3">{{ $a->normal_balance }}</td>
                    <td class="p-3">{{ $a->postable ? 'Yes' : 'No' }}</td>
                    <td class="p-3"><span class="{{ $a->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $a->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
