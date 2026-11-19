<x-app-layout>
    <x-slot name="header">{{ __('Documents') }} — {{ $employee->full_name }}</x-slot>

    @if ($canUpload)
    <div class="mb-6 rounded-sm border border-gray-200 bg-white p-4 shadow-sm" x-data="{ open: false }">
        <button @click="open = !open" class="text-sm font-semibold text-xerin-700">+ Upload Document</button>
        <form x-show="open" x-cloak method="POST" action="{{ route('employee.documents.store', $employee) }}" enctype="multipart/form-data" class="mt-3 grid grid-cols-1 sm:grid-cols-5 gap-3">
            @csrf
            <select name="category_id" required class="rounded-sm border-gray-300 text-sm">
                <option value="">Category *</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}{{ $cat->is_confidential ? ' (confidential)' : '' }}</option>
                @endforeach
            </select>
            <input name="title" placeholder="Title *" required class="rounded-sm border-gray-300 text-sm">
            <input type="date" name="expires_at" class="rounded-sm border-gray-300 text-sm" title="Expiry (if applicable)">
            <input type="file" name="file" required class="text-sm">
            <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Upload</button>
        </form>
    </div>
    @endif

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                <th class="px-4 py-3 text-left">Title</th><th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-left">Version</th><th class="px-4 py-3 text-left">Expires</th>
                <th class="px-4 py-3 text-left">Uploaded By</th><th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($employee->documents as $doc)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $doc->title }}</td>
                        <td class="px-4 py-3">{{ $doc->category->name }}</td>
                        <td class="px-4 py-3 text-xs">v{{ $doc->version }}</td>
                        <td class="px-4 py-3 text-xs {{ $doc->isExpired() ? 'text-red-600 font-semibold' : ($doc->expiringSoon() ? 'text-amber-600' : '') }}">
                            {{ $doc->expires_at?->toDateString() ?? '—' }}{{ $doc->isExpired() ? ' (expired)' : '' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $doc->uploader?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('employee.documents.download', $doc) }}" class="text-xs font-semibold text-xerin-700 hover:underline">Download</a>
                            @if ($canDelete)
                                <form method="POST" action="{{ route('employee.documents.destroy', $doc) }}" class="inline" onsubmit="return confirm('Remove this document?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No documents.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
