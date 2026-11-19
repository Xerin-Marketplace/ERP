@php $d = $department ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="department_definition_id" value="Classification (optional)" />
        <select id="department_definition_id" name="department_definition_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">— Custom —</option>
            @foreach ($definitions as $def)
                <option value="{{ $def->id }}" @selected(old('department_definition_id', $d?->department_definition_id) == $def->id)>{{ $def->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="name" value="Department name *" />
        <x-text-input id="name" name="name" class="block mt-1.5 w-full" :value="old('name', $d?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="code" value="Code *" />
        <x-text-input id="code" name="code" class="block mt-1.5 w-full uppercase" :value="old('code', $d?->code)" required placeholder="e.g. IT-DSM" />
        <x-input-error :messages="$errors->get('code')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="company_id" value="Company *" />
        <select id="company_id" name="company_id" required class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="" disabled @selected(!old('company_id', $d?->company_id))>Select company…</option>
            @foreach ($companies as $c)
                <option value="{{ $c->id }}" @selected(old('company_id', $d?->company_id) == $c->id)>{{ $c->legal_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('company_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="branch_id" value="Branch (optional — leave empty for company-wide)" />
        <select id="branch_id" name="branch_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">— Company-wide —</option>
            @foreach ($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id', $d?->branch_id) == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('branch_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="parent_id" value="Parent department (optional)" />
        <select id="parent_id" name="parent_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">— Top-level —</option>
            @foreach ($parents as $p)
                <option value="{{ $p->id }}" @selected(old('parent_id', $d?->parent_id) == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="email" value="Department email" />
        <x-text-input id="email" name="email" type="email" class="block mt-1.5 w-full" :value="old('email', $d?->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="phone" value="Department phone" />
        <x-text-input id="phone" name="phone" class="block mt-1.5 w-full" :value="old('phone', $d?->phone)" />
    </div>
    <div>
        <x-input-label for="effective_date" value="Effective date" />
        <x-text-input id="effective_date" name="effective_date" type="date" class="block mt-1.5 w-full" :value="old('effective_date', $d?->effective_date?->format('Y-m-d'))" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">{{ old('description', $d?->description) }}</textarea>
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <x-primary-button>{{ $d ? 'Save changes' : 'Create department' }}</x-primary-button>
    <a href="{{ $d ? route('organization.departments.show', $d) : route('organization.departments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
