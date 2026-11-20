<x-app-layout>
    <x-slot name="header">Access Workflows</x-slot>
    <div class="space-y-4">
        @foreach ($workflows as $item)
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-semibold text-sm">{{ $item['workflow']->name }}
                            <span class="text-xs font-normal text-gray-400">v{{ $item['workflow']->current_version }} · {{ str_replace('_',' ',$item['workflow']->request_type) }}</span></h3>
                        <p class="text-xs text-gray-500">{{ $item['workflow']->branch->name ?? 'All branches' }} · {{ $item['workflow']->department->name ?? 'All departments' }}</p>
                    </div>
                    <form method="POST" action="{{ route('reception.access.workflows.toggle', $item['workflow']) }}">@csrf
                        <button class="text-xs font-semibold {{ $item['workflow']->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $item['workflow']->is_active ? 'Active' : 'Inactive' }}</button></form>
                </div>
                @if ($item['version'])
                    <ol class="mt-3 ml-4 list-decimal text-xs text-gray-600 space-y-1">
                        @foreach ($item['version']->stages as $s)
                            <li>{{ $s->name }} — <em>{{ $s->approver_role }}</em>@if ($s->sla_hours) · SLA {{ $s->sla_hours }}h @endif</li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endforeach
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold">New Workflow</h3>
            <form method="POST" action="{{ route('reception.access.workflows.store') }}" x-data="{ stages: [{name:'',approver_role:'',sla_hours:48}] }" class="mt-3 space-y-3">
                @csrf
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <input name="name" required placeholder="Workflow name" class="rounded-sm border-gray-300 text-sm">
                    <select name="request_type" class="rounded-sm border-gray-300 text-sm">
                        <option value="new_employee">New employee</option><option value="existing_employee">Existing employee</option>
                        <option value="external">External</option><option value="*">All types</option>
                    </select>
                    <input name="branch_id" placeholder="Branch ID (optional)" class="rounded-sm border-gray-300 text-sm">
                    <input name="department_id" placeholder="Dept ID (optional)" class="rounded-sm border-gray-300 text-sm">
                </div>
                <template x-for="(s, i) in stages" :key="i">
                    <div class="grid grid-cols-3 gap-3">
                        <input :name="`stages[${i}][name]`" x-model="s.name" required placeholder="Stage name" class="rounded-sm border-gray-300 text-sm">
                        <select :name="`stages[${i}][approver_role]`" class="rounded-sm border-gray-300 text-sm">
                            @foreach ($roles as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach
                        </select>
                        <input :name="`stages[${i}][sla_hours]`" x-model="s.sla_hours" type="number" min="1" placeholder="SLA hours" class="rounded-sm border-gray-300 text-sm">
                    </div>
                </template>
                <div class="flex gap-2">
                    <button type="button" @click="stages.push({name:'',approver_role:'',sla_hours:48})" class="text-xs font-semibold text-xerin-700">+ Add stage</button>
                    <button class="rounded-sm bg-gray-800 px-4 py-1.5 text-xs font-semibold text-white">Create Workflow</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
