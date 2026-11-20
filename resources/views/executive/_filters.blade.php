<form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-sm border border-gray-200 bg-white p-3 shadow-sm">
    <div>
        <label class="text-xs text-gray-500">Period</label>
        <select name="range" class="block rounded-sm border-gray-300 text-sm" onchange="this.form.submit()">
            @foreach ($presets as $k => $v)
                <option value="{{ $k }}" @selected($range === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs text-gray-500">From</label>
        <input type="date" name="from" value="{{ $from }}" class="block rounded-sm border-gray-300 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500">To</label>
        <input type="date" name="to" value="{{ $to }}" class="block rounded-sm border-gray-300 text-sm">
    </div>
    <div>
        <label class="text-xs text-gray-500">Branch</label>
        <select name="branch" class="block rounded-sm border-gray-300 text-sm">
            <option value="">All branches</option>
            @foreach ($branches as $b)
                <option value="{{ $b->id }}" @selected(($branchId ?? null) == $b->id)>{{ $b->code }} — {{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Apply</button>
    <span class="ml-auto text-xs text-gray-400">{{ $from }} → {{ $to }} · refreshed {{ $refreshedAt->format('H:i') }}</span>
</form>
