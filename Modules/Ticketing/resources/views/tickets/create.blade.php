<x-app-layout>
    <x-slot name="header">{{ __('New Ticket') }}</x-slot>
    <form method="POST" action="{{ route('ticketing.store') }}" enctype="multipart/form-data" class="rounded-sm border border-gray-200 bg-white p-6 shadow-sm max-w-2xl space-y-4">
        @csrf
        <div><label class="block text-xs text-gray-500 mb-1">Subject *</label><input name="subject" required value="{{ old('subject') }}" class="w-full rounded-sm border-gray-300 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">Description *</label><textarea name="description" required rows="5" class="w-full rounded-sm border-gray-300 text-sm">{{ old('description') }}</textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs text-gray-500 mb-1">Type</label>
                <select name="ticket_type_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">—</option>
                    @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-gray-500 mb-1">Category</label>
                <select name="category_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">—</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}{{ $c->parent ? ' — '.$c->parent->name : '' }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-gray-500 mb-1">Priority</label>
                <select name="priority" class="w-full rounded-sm border-gray-300 text-sm">
                    @foreach (\Modules\Ticketing\Models\Ticket::PRIORITIES as $p)<option value="{{ $p }}" @selected($p==='normal')>{{ ucfirst($p) }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-gray-500 mb-1">Severity</label>
                <select name="severity" class="w-full rounded-sm border-gray-300 text-sm">
                    @foreach (\Modules\Ticketing\Models\Ticket::SEVERITIES as $s)<option value="{{ $s }}" @selected($s==='minor')>{{ ucfirst($s) }}</option>@endforeach</select></div>
        </div>
        <div><label class="block text-xs text-gray-500 mb-1">Attachments</label><input type="file" name="attachments[]" multiple class="text-sm"></div>
        <button class="rounded-sm bg-xerin-600 px-5 py-2 text-sm font-semibold text-white">Submit Ticket</button>
    </form>
</x-app-layout>
