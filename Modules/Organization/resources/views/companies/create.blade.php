<x-app-layout>
    <x-slot name="header">{{ __('Create Company') }}</x-slot>

    <div class="max-w-4xl rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.companies.store') }}">
            @csrf
            @include('organization::companies._form')
        </form>
    </div>
</x-app-layout>
