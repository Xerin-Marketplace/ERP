{{-- Fire toasts for session flashes and validation errors --}}
<script>
    window.addEventListener('DOMContentLoaded', () => {
        @if ($errors->any())
            window.toast(@json($errors->first()), 'error');
        @elseif (session('error'))
            window.toast(@json(session('error')), 'error');
        @elseif (session('success') ?? session('status'))
            window.toast(@json(session('success') ?? session('status')), 'success');
        @endif
    });
</script>
