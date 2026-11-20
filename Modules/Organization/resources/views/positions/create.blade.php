<x-app-layout>
    <x-slot name="header">Create Position</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.positions.store') }}">
            @csrf
            @include('organization::positions._form')
        </form>
    </div>
</x-app-layout>
