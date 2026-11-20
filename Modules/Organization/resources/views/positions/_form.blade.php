@php $p = $position ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="code" value="Position code *" />
        <x-text-input id="code" name="code" class="block mt-1.5 w-full uppercase" :value="old('code', $p?->code)" required placeholder="e.g. IT-MGR" />
        <x-input-error :messages="$errors->get('code')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="title" value="Job title *" />
        <x-text-input id="title" name="title" class="block mt-1.5 w-full" :value="old('title', $p?->title)" required />
        <x-input-error :messages="$errors->get('title')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="company_id" value="Company *" />
        <select id="company_id" name="company_id" required class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            @foreach ($companies as $c)
                <option value="{{ $c->id }}" @selected(old('company_id', $p?->company_id) == $c->id)>{{ $c->legal_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('company_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="department_id" value="Department" />
        <select id="department_id" name="department_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">—</option>
            @foreach ($departments as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $p?->department_id) == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="job_grade_id" value="Job grade" />
        <select id="job_grade_id" name="job_grade_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">—</option>
            @foreach ($grades as $g)
                <option value="{{ $g->id }}" @selected(old('job_grade_id', $p?->job_grade_id) == $g->id)>{{ $g->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="reports_to_position_id" value="Reports to" />
        <select id="reports_to_position_id" name="reports_to_position_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">—</option>
            @foreach ($parents as $par)
                @if (! $p || $par->id !== $p->id)
                    <option value="{{ $par->id }}" @selected(old('reports_to_position_id', $p?->reports_to_position_id) == $par->id)>{{ $par->title }}</option>
                @endif
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('reports_to_position_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="category" value="Category" />
        <x-text-input id="category" name="category" class="block mt-1.5 w-full" :value="old('category', $p?->category)" placeholder="e.g. Management, Technical" />
    </div>
    @if ($p)
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
                @foreach (['active', 'inactive'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $p->status) === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">{{ old('description', $p?->description) }}</textarea>
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <x-primary-button>{{ $p ? 'Save changes' : 'Create position' }}</x-primary-button>
    <a href="{{ route('organization.positions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
