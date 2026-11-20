<x-app-layout>
    <x-slot name="header">{{ __('Backup & Recovery') }}</x-slot>

    <div class="rounded-sm border border-amber-200 bg-amber-50 shadow-sm p-6 max-w-2xl">
        <h2 class="font-semibold text-amber-900">No backup subsystem configured</h2>
        <p class="mt-2 text-sm text-amber-800">
            This deployment has no verified backup jobs registered. This page intentionally does not
            display fabricated backup records — a backup dashboard only reports real, verified job output.
        </p>
        <div class="mt-4 text-sm text-amber-800 space-y-1.5">
            <p>To enable backups, the deployment must provide:</p>
            <ul class="list-disc ps-5 space-y-1">
                <li>A scheduled backup job (e.g. <code>backup:run</code> or infrastructure-level snapshots)</li>
                <li>An off-site storage destination with encryption</li>
                <li>A verified restore procedure tested end-to-end</li>
            </ul>
            <p class="mt-3">Once implemented, backup records will appear here with status, size and verification results.</p>
        </div>
    </div>
</x-app-layout>
