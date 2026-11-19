{{-- Status transition buttons. $route = status endpoint, $status = current EntityStatus --}}
@php $val = $status instanceof \Modules\Organization\Enums\EntityStatus ? $status->value : $status; @endphp
<div class="flex flex-wrap gap-2">
    @if ($val !== 'active')
        <form method="POST" action="{{ $route }}" onsubmit="return confirm('Activate this record?')">
            @csrf <input type="hidden" name="status" value="active">
            <button class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Activate</button>
        </form>
    @endif
    @if ($val === 'active')
        <form method="POST" action="{{ $route }}" onsubmit="return confirm('Suspend this record?')">
            @csrf <input type="hidden" name="status" value="suspended">
            <button class="rounded-md bg-amber-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-600">Suspend</button>
        </form>
    @endif
    @if ($val !== 'archived')
        <form method="POST" action="{{ $route }}" onsubmit="return confirm('Archive this record? This preserves its history.')">
            @csrf <input type="hidden" name="status" value="archived">
            <button class="rounded-md bg-gray-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-700">Archive</button>
        </form>
    @endif
</div>
