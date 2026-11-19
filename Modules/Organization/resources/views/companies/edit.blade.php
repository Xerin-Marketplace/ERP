<x-app-layout>
    <x-slot name="header">{{ __('Edit Company') }}</x-slot>

    <div class="max-w-4xl rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.companies.update', $company) }}">
            @csrf @method('PUT')
            @include('organization::companies._form', ['company' => $company])
        </form>
    </div>
</x-app-layout>
