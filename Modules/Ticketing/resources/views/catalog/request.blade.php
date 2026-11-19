<x-app-layout>
    <x-slot name="header">{{ __('Request') }} — {{ $service->name }}</x-slot>
    <form method="POST" action="{{ route('ticketing.catalog.submit', $service) }}" enctype="multipart/form-data" class="rounded-sm border border-gray-200 bg-white p-6 shadow-sm max-w-2xl space-y-4">
        @csrf
        <p class="text-sm text-gray-500">{{ $service->description }}</p>
        <div><label class="block text-xs text-gray-500 mb-1">Subject *</label><input name="subject" required value="{{ old('subject', $service->name) }}" class="w-full rounded-sm border-gray-300 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">Details *</label><textarea name="description" required rows="4" class="w-full rounded-sm border-gray-300 text-sm">{{ old('description') }}</textarea></div>
        @foreach ($service->formFields as $f)
            <div><label class="block text-xs text-gray-500 mb-1">{{ $f->label }}{{ $f->required ? ' *' : '' }}</label>
                @if ($f->type === 'textarea')
                    <textarea name="custom[{{ $f->key }}]" {{ $f->required ? 'required' : '' }} class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                @elseif ($f->type === 'select')
                    <select name="custom[{{ $f->key }}]" {{ $f->required ? 'required' : '' }} class="w-full rounded-sm border-gray-300 text-sm">
                        @foreach ($f->options ?? [] as $o)<option>{{ $o }}</option>@endforeach</select>
                @else
                    <input type="{{ $f->type === 'date' ? 'date' : ($f->type === 'number' ? 'number' : 'text') }}" name="custom[{{ $f->key }}]" {{ $f->required ? 'required' : '' }} class="w-full rounded-sm border-gray-300 text-sm">
                @endif
            </div>
        @endforeach
        <div><label class="block text-xs text-gray-500 mb-1">Attachments{{ $service->requires_attachment ? ' *' : '' }}</label>
            <input type="file" name="attachments[]" multiple {{ $service->requires_attachment ? 'required' : '' }} class="text-sm"></div>
        <button class="rounded-sm bg-xerin-600 px-5 py-2 text-sm font-semibold text-white">Submit Request</button>
    </form>
</x-app-layout>
