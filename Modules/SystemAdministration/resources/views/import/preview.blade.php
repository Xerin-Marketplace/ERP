<x-app-layout>
    <x-slot name="header">Import #{{ $import->id }} — Preview</x-slot>
    <div class="space-y-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 text-sm">
            {{ $import->total_rows }} rows · {{ collect($rows)->where('ok', true)->count() }} valid · {{ $import->error_count }} errors
        </div>
        <form method="POST" action="{{ route('systemadmin.import.commit', $import) }}">
            @csrf
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-left"><tr>
                        <th class="p-2">Row</th><th class="p-2">Status</th>
                        @foreach (array_keys($import->mapping ? array_filter($import->mapping) : []) as $f)<th class="p-2">{{ $f }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                    @foreach (array_slice($rows, 0, 100) as $r)
                        <tr class="border-t {{ $r['ok'] ? '' : 'bg-red-50' }}">
                            <td class="p-2">{{ $r['line'] }}</td>
                            <td class="p-2">{{ $r['ok'] ? '✓' : '✗' }}</td>
                            @foreach (array_keys(array_filter($import->mapping ?? [])) as $f)
                                <td class="p-2">{{ $r['data'][$f] ?? '—' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($import->error_count)
            <div class="mt-3 rounded-sm border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                @foreach (array_slice($errors, 0, 20) as $e) <p>Row {{ $e['row'] }}: {{ $e['message'] }}</p> @endforeach
                @if ($import->error_count > 20)<p>…and {{ $import->error_count - 20 }} more.</p>@endif
            </div>
            @endif
            <button class="mt-4 rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">
                Commit {{ collect($rows)->where('ok', true)->count() }} valid rows</button>
        </form>
    </div>
</x-app-layout>
