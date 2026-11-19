<x-app-layout>
    <x-slot name="header">{{ $article->reference }} — {{ $article->title }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white p-6 shadow-sm max-w-3xl">
        <p class="text-xs text-gray-400 mb-4">{{ $article->category?->name ?? 'Uncategorized' }} · by {{ $article->author?->name ?? 'System' }} · {{ $article->view_count }} views</p>
        <div class="prose-sm text-sm whitespace-pre-wrap">{{ $article->body }}</div>
    </div>
</x-app-layout>
