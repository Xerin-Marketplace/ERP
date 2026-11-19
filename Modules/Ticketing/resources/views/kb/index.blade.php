<x-app-layout>
    <x-slot name="header">{{ __('Knowledge Base') }}</x-slot>
    <div class="mb-4 flex gap-3">
        <form method="GET" class="flex gap-2"><input name="search" value="{{ request('search') }}" placeholder="Search articles" class="rounded-sm border-gray-300 text-sm"><button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Search</button></form>
        @if ($canManage)
        <div x-data="{open:false}" class="ml-auto relative">
            <button @click="open=!open" class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">+ New Article</button>
            <form x-show="open" x-cloak method="POST" action="{{ route('ticketing.kb.store') }}" class="absolute right-0 mt-2 w-96 rounded-sm border bg-white p-4 shadow-lg z-10 space-y-2">
                @csrf
                <input name="title" required placeholder="Title *" class="w-full rounded-sm border-gray-300 text-sm">
                <select name="category_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Category</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <textarea name="body" required rows="5" placeholder="Article content" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                <select name="status" class="w-full rounded-sm border-gray-300 text-sm"><option value="draft">Draft</option><option value="published">Publish</option></select>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
            </form>
        </div>
        @endif
    </div>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
        @forelse ($articles as $a)
            <a href="{{ route('ticketing.kb.show', $a) }}" class="block px-5 py-3 hover:bg-gray-50">
                <p class="font-medium text-sm">{{ $a->reference }} — {{ $a->title }}
                    @if ($a->status !== 'published') <span class="text-xs text-amber-600">(draft)</span> @endif</p>
                <p class="text-xs text-gray-400">{{ $a->category?->name ?? 'Uncategorized' }} · {{ $a->view_count }} views · {{ $a->helpful_count }} helpful</p>
            </a>
        @empty <p class="px-5 py-8 text-center text-gray-400">No articles yet.</p> @endforelse
    </div>
    <div class="mt-4">{{ $articles->links() }}</div>
</x-app-layout>
