<x-app-layout>
    <x-slot name="header">Edit Position — {{ $position->title }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.positions.update', $position) }}">
            @csrf @method('PUT')
            @include('organization::positions._form')
        </form>
    </div>
</x-app-layout>
