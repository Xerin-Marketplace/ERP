<div x-data="{
        toasts: [],
        add(t) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: 'success', ...t });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-on:toast.window="add($event.detail)"
    class="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-full max-w-xs px-4 sm:px-0 pointer-events-none">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="pointer-events-auto flex items-start gap-3 rounded-sm border border-gray-200 bg-white px-4 py-3 shadow-lg">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                  :class="t.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'">
                <svg x-show="t.type !== 'error'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <svg x-show="t.type === 'error'" x-cloak class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </span>
            <p class="flex-1 text-sm font-medium text-gray-800" x-text="t.message"></p>
            <button x-on:click="remove(t.id)" class="shrink-0 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
