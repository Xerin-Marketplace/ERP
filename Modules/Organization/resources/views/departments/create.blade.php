<x-app-layout>
    <x-slot name="header">{{ __('Create Department') }}</x-slot>

    <div class="max-w-3xl rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.departments.store') }}">
            @csrf
            @include('organization::departments._form')
        </form>
    </div>
</x-app-layout>
