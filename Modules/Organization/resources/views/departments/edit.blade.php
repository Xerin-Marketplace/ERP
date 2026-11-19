<x-app-layout>
    <x-slot name="header">{{ __('Edit Department') }}</x-slot>

    <div class="max-w-3xl rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.departments.update', $department) }}">
            @csrf @method('PUT')
            @include('organization::departments._form', ['department' => $department])
        </form>
    </div>
</x-app-layout>
