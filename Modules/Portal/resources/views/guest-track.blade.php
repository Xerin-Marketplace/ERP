<x-guest-layout>
    <div class="max-w-lg mx-auto py-12 px-4">
        <h1 class="text-xl font-bold text-gray-900">{{ __('portal.track_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('portal.track_sub') }}</p>
        <form method="POST" action="{{ route('track.lookup') }}" class="mt-6 space-y-4 rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <div><label class="block text-xs font-semibold text-gray-600">{{ __('portal.tracking_no') }}</label>
                <input name="tracking_no" value="{{ old('tracking_no') }}" placeholder="XRN000000001" required class="mt-1 w-full rounded-sm border-gray-300 font-mono text-sm">
                @error('tracking_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror</div>
            <div><label class="block text-xs font-semibold text-gray-600">{{ __('portal.contact') }}</label>
                <input name="contact" value="{{ old('contact') }}" required class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                @error('contact') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror</div>
            <button class="rounded-sm bg-xerin-700 px-5 py-2 text-sm font-semibold text-white">{{ __('portal.track') }}</button>
        </form>
        @if ($searched)
            @if ($result)
                <div class="mt-6 rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between items-center">
                        <span class="font-mono font-bold">{{ $result['tracking_no'] }}</span>
                        <span class="rounded-sm bg-xerin-50 px-2 py-0.5 text-xs font-semibold text-xerin-700">{{ ucfirst($result['label']) }}</span>
                    </div>
                    @if ($result['events'])
                    <div class="mt-4 space-y-2">
                        @foreach (array_reverse($result['events']) as $e)
                            <div class="flex gap-3 text-xs"><span class="text-gray-400">{{ \Illuminate\Support\Carbon::parse($e['at'])->format('d M H:i') }}</span>
                                @php $ek = 'portal.events.'.$e['event']; @endphp
                                <span>{{ \Illuminate\Support\Facades\Lang::has($ek) ? __($ek) : ucfirst($e['event']) }}</span></div>
                        @endforeach
                    </div>
                    @endif
                </div>
            @else
                <p class="mt-4 text-sm text-red-600">{{ __('portal.not_found') }}</p>
            @endif
        @endif
    </div>
</x-guest-layout>
