<x-app-layout>
    <x-slot name="header">{{ __('System Announcements') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-3">
            @forelse ($announcements as $a)
                <div class="rounded-sm border bg-white shadow-sm {{ $a->level === 'danger' ? 'border-red-200' : ($a->level === 'warning' ? 'border-amber-200' : 'border-gray-200') }}">
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $a->title }}</p>
                            <span class="rounded-sm px-2 py-0.5 text-[10px] font-semibold uppercase {{ $a->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $a->is_active ? 'live' : 'off' }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ $a->body }}</p>
                        <p class="mt-2 text-xs text-gray-400">
                            {{ $a->level }} · {{ $a->audience }}@if ($a->requires_ack) · requires acknowledgement @endif
                            @if ($a->starts_at || $a->ends_at) · {{ $a->starts_at?->format('Y-m-d H:i') ?? 'now' }} → {{ $a->ends_at?->format('Y-m-d H:i') ?? 'open' }}@endif
                            · by {{ $a->createdBy?->name ?? 'system' }}
                        </p>
                    </div>
                    <div class="flex gap-2 border-t border-gray-100 px-5 py-3">
                        <form method="POST" action="{{ route('systemadmin.announcements.update', $a) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="title" value="{{ $a->title }}"><input type="hidden" name="body" value="{{ $a->body }}">
                            <input type="hidden" name="level" value="{{ $a->level }}"><input type="hidden" name="audience" value="{{ $a->audience }}">
                            <input type="hidden" name="is_active" value="{{ $a->is_active ? 0 : 1 }}">
                            <button class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">{{ $a->is_active ? 'Deactivate' : 'Activate' }}</button>
                        </form>
                        <form method="POST" action="{{ route('systemadmin.announcements.destroy', $a) }}" onsubmit="return confirm('Delete this announcement?')">
                            @csrf @method('DELETE')
                            <button class="rounded-md bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-sm border border-gray-200 bg-white p-10 text-center text-sm text-gray-400 shadow-sm">No announcements.</div>
            @endforelse
        </div>

        <form method="POST" action="{{ route('systemadmin.announcements.store') }}" class="rounded-sm border border-gray-200 bg-white shadow-sm self-start">
            @csrf
            <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">New announcement</h2></div>
            <div class="p-5 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700">Title</label>
                    <input name="title" type="text" required maxlength="120" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea name="body" required rows="3" maxlength="2000" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-sm font-medium text-gray-700">Level</label>
                        <select name="level" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="info">Info</option><option value="warning">Warning</option><option value="danger">Critical</option>
                        </select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Audience</label>
                        <select name="audience" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="all">All users</option><option value="admins">Administrators</option>
                        </select></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-sm font-medium text-gray-700">Starts</label>
                        <input name="starts_at" type="datetime-local" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Ends</label>
                        <input name="ends_at" type="datetime-local" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600"> Publish immediately</label>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="requires_ack" value="1" class="rounded border-gray-300 text-indigo-600"> Requires acknowledgement</label>
            </div>
            <div class="border-t border-gray-100 px-5 py-4"><button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Publish</button></div>
        </form>
    </div>
</x-app-layout>
