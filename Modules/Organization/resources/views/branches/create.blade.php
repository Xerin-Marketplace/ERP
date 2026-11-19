<x-app-layout>
    <x-slot name="header">{{ __('Create Branch') }}</x-slot>

    <div class="max-w-4xl rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.branches.store') }}">
            @csrf
            @include('organization::branches._form')
        </form>
    </div>
</x-app-layout>
