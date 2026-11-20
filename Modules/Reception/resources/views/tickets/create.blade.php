<x-app-layout>
    <x-slot name="header">{{ __('New IT Ticket') }}</x-slot>

    <div class="max-w-2xl rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('reception.tickets.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Employee requesting support *</label>
                <select name="employee_id" required class="w-full rounded-sm border-gray-300 text-sm">
                    <option value="">Select employee…</option>
                    @foreach ($employees as $e)
                        <option value="{{ $e->id }}" @selected(old('employee_id')==$e->id)>{{ $e->full_name }} — {{ $e->employee_number }}</option>
                    @endforeach</select>
                @error('employee_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Category</label>
                    <select name="category_id" class="w-full rounded-sm border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Priority *</label>
                    <select name="priority" required class="w-full rounded-sm border-gray-300 text-sm">
                        @foreach ($priorities as $p)<option value="{{ $p }}" @selected(old('priority','normal')===$p)>{{ ucfirst($p) }}</option>@endforeach</select>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Subject *</label>
                <input name="subject" required maxlength="200" value="{{ old('subject') }}" class="w-full rounded-sm border-gray-300 text-sm">
                @error('subject')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Description *</label>
                <textarea name="description" required rows="4" maxlength="5000" class="w-full rounded-sm border-gray-300 text-sm">{{ old('description') }}</textarea>
                @error('description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Office location</label>
                <input name="location" maxlength="200" value="{{ old('location') }}" class="w-full rounded-sm border-gray-300 text-sm" placeholder="e.g. Floor 2, Finance office">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Attachments (PNG/JPG/PDF/MP4, ≤20MB each)</label>
                <input type="file" name="attachments[]" multiple accept=".png,.jpg,.jpeg,.pdf,.mp4,.mov" class="text-sm">
                @error('attachments.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button class="rounded-sm bg-xerin-600 px-5 py-2 text-sm font-semibold text-white">Submit Ticket</button>
        </form>
    </div>
</x-app-layout>
