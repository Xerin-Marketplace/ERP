<x-app-layout>
    <x-slot name="header">{{ __('Onboarding Workspace') }}</x-slot>

    <div class="space-y-4">
        @forelse ($records as $record)
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm" x-data="{ open: false }">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('employee.show', $record->employee) }}" class="font-semibold text-xerin-700 hover:underline">{{ $record->employee->full_name }}</a>
                        <p class="text-xs text-gray-400">{{ $record->employee->employee_number }} · {{ $record->employee->company?->legal_name ?? '—' }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-40">
                            <div class="h-2 rounded bg-gray-100 overflow-hidden"><div class="h-2 bg-xerin-600" style="width:{{ $record->progress() }}%"></div></div>
                            <p class="mt-1 text-right text-xs text-gray-500">{{ $record->progress() }}%</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $record->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst(str_replace('_',' ',$record->status)) }}</span>
                        <button @click="open = !open" class="text-xs font-semibold text-gray-500 hover:text-gray-800" x-text="open ? 'Hide' : 'Tasks'"></button>
                    </div>
                </div>
                <div x-show="open" x-cloak class="mt-4 border-t border-gray-100 pt-3">
                    <ul class="space-y-2 text-sm">
                        @foreach ($record->tasks as $task)
                            <li class="flex items-center justify-between gap-4">
                                <span class="{{ $task->status === 'completed' ? 'line-through text-gray-400' : 'text-gray-700' }}">{{ $task->title }}</span>
                                @if ($record->status !== 'completed')
                                    <form method="POST" action="{{ route('employee.onboarding.task', $task) }}" class="flex gap-1">
                                        @csrf
                                        @foreach (['in_progress' => 'Start', 'completed' => 'Done', 'skipped' => 'Skip'] as $s => $l)
                                            @if ($task->status !== $s)
                                                <button name="status" value="{{ $s }}" class="text-xs font-semibold {{ $s === 'completed' ? 'text-green-700' : 'text-gray-500' }} hover:underline">{{ $l }}</button>
                                            @endif
                                        @endforeach
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">{{ ucfirst($task->status) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @empty
            <div class="rounded-sm border border-gray-200 bg-white p-8 text-center text-gray-400 shadow-sm">No onboarding records. Start one from an employee's profile.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
</x-app-layout>
