<x-app-layout>
    <x-slot name="header">{{ __('Edit Branch') }}</x-slot>

    <div class="max-w-4xl rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.branches.update', $branch) }}">
            @csrf @method('PUT')
            @include('organization::branches._form', ['branch' => $branch])
        </form>
    </div>
</x-app-layout>
