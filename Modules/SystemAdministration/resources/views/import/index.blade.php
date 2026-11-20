<x-app-layout>
    <x-slot name="header">Data Import — Excel/CSV Migration</x-slot>
    <div class="max-w-3xl">
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h4 class="text-sm font-semibold">New import</h4>
            <form method="POST" action="{{ route('systemadmin.import.store') }}" enctype="multipart/form-data" class="mt-3 flex items-end gap-3">
                @csrf
                <select name="entity" required class="rounded-sm border-gray-300 text-sm">
                    <option value="customers">Customers</option>
                    <option value="suppliers">Suppliers</option>
                </select>
                <input type="file" name="file" required accept=".csv,.xlsx,.xls" class="text-sm">
                <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Upload &amp; Map</button>
            </form>
            <p class="mt-2 text-xs text-gray-400">Upload → map columns → preview with validation → commit. Spelling variants normalize through the alias table.</p>
        </div>

        <div class="mt-6 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left"><tr>
                    <th class="p-3">Entity</th><th class="p-3">File</th><th class="p-3">Rows</th>
                    <th class="p-3">Imported</th><th class="p-3">Errors</th><th class="p-3">Status</th><th class="p-3">By</th><th class="p-3"></th>
                </tr></thead>
                <tbody>
                @forelse ($imports as $i)
                    <tr class="border-t">
                        <td class="p-3">{{ $i->entity }}</td>
                        <td class="p-3 text-xs">{{ $i->original_name }}</td>
                        <td class="p-3">{{ $i->total_rows }}</td>
                        <td class="p-3">{{ $i->imported_count }}</td>
                        <td class="p-3 {{ $i->error_count ? 'text-red-600' : '' }}">{{ $i->error_count }}</td>
                        <td class="p-3"><span class="rounded-sm px-2 py-0.5 text-xs font-medium {{ $i->status === 'imported' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $i->status }}</span></td>
                        <td class="p-3 text-xs">{{ $i->user->name ?? '—' }}</td>
                        <td class="p-3"><a href="{{ route('systemadmin.import.show', $i) }}" class="text-xerin-700 text-xs font-semibold">Open</a></td>
                    </tr>
                @empty <tr><td colspan="8" class="p-6 text-center text-gray-400">No imports yet.</td></tr> @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h4 class="text-sm font-semibold">Spelling alias</h4>
            <form method="POST" action="{{ route('systemadmin.import.alias') }}" class="mt-3 grid grid-cols-2 gap-3">
                @csrf
                <select name="entity" class="rounded-sm border-gray-300 text-sm">
                    <option value="customers">customers</option><option value="suppliers">suppliers</option>
                </select>
                <input name="field" required placeholder="Field (e.g. category)" class="rounded-sm border-gray-300 text-sm">
                <input name="alias" required placeholder="Raw value (e.g. eletronics)" class="rounded-sm border-gray-300 text-sm">
                <input name="canonical" required placeholder="Canonical (e.g. electronics)" class="rounded-sm border-gray-300 text-sm">
                <button class="col-span-2 rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Save alias</button>
            </form>
        </div>
    </div>
</x-app-layout>
