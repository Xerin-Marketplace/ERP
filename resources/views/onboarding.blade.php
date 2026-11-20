<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Welcome to XERIN ERP</h2></x-slot>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Account setup</h3>
                <span class="text-sm {{ $complete ? 'text-green-600 font-semibold' : 'text-amber-600' }}">
                    {{ $complete ? 'Complete ✓' : 'In progress' }}
                </span>
            </div>
            @php $done = collect($steps)->where('done', true)->count(); @endphp
            <div class="h-2 bg-gray-100 rounded-full mb-6 overflow-hidden">
                <div class="h-full bg-blue-600 rounded-full transition-all" style="width: {{ round($done / count($steps) * 100) }}%"></div>
            </div>
            <ul class="space-y-3">
                @foreach($steps as $s)
                <li class="flex items-center gap-3 {{ $s['done'] ? 'text-gray-800' : 'text-gray-400' }}">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $s['done'] ? 'bg-green-100 text-green-700' : 'bg-gray-100' }}">{{ $s['done'] ? '✓' : '·' }}</span>
                    {{ $s['label'] }}
                    @if(!$s['done'] && $s['label'] === 'Phone number added')
                        <a href="{{ route('profile.edit') }}" class="text-xs text-blue-600 underline">add in profile</a>
                    @endif
                </li>
                @endforeach
            </ul>
            <div class="mt-6 pt-4 border-t">
                @if($complete)
                    <a href="{{ route('dashboard') }}" class="inline-block bg-blue-600 text-white rounded-lg px-5 py-2 text-sm font-medium hover:bg-blue-500">Go to dashboard →</a>
                @else
                    <p class="text-sm text-gray-500">Finish the remaining steps — then <a class="text-blue-600 underline" href="{{ route('dashboard') }}">continue to your dashboard</a>.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
