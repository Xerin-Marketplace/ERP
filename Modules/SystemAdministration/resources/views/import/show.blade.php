<x-app-layout>
    <x-slot name="header">Import #{{ $import->id }} — {{ $import->original_name }}</x-slot>
    <div class="max-w-4xl space-y-6">
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm text-sm">
            Entity: <strong>{{ $import->entity }}</strong> · Status: <strong>{{ $import->status }}</strong>
            · Rows: {{ $import->total_rows }} · Imported: {{ $import->imported_count }} · Errors: {{ $import->error_count }}
        </div>
        @if ($import->status === 'imported')
            <div class="rounded-sm border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Import complete — {{ $import->imported_count }} records created, {{ $import->skipped_count }} skipped.</div>
        @endif

        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h4 class="text-sm font-semibold">Map columns → fields</h4>
            <form method="POST" action="{{ route('systemadmin.import.preview', $import) }}" class="mt-3 space-y-2">
                @csrf
                @foreach ($headers as $h)
                <div class="grid grid-cols-2 items-center gap-3 text-sm">
                    <span class="font-mono text-xs">{{ $h }}</span>
                    <select name="mapping[{{ $h }}]" class="rounded-sm border-gray-300 text-sm">
                        <option value="">— skip —</option>
                        @foreach ($fields as $f)
                            <option value="{{ $f }}" @selected(($suggested[$h] ?? null) === $f)>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
                <button class="mt-3 rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Preview &amp; Validate</button>
            </form>
        </div>
    </div>
</x-app-layout>
