<div x-data="toasterHub(@js($toasts), @js($config))"
     @class([
         'fixed z-50 p-4 w-full flex flex-col pointer-events-none sm:p-6',
         'bottom-0' => $alignment->is('bottom'),
         'top-1/2 -translate-y-1/2' => $alignment->is('middle'),
         'top-0' => $alignment->is('top'),
         'items-start rtl:items-end' => $position->is('left'),
         'items-center' => $position->is('center'),
         'items-end rtl:items-start' => $position->is('right'),
     ])
     role="status"
     id="toaster">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.isVisible"
             x-init="$nextTick(() => toast.show($el))"
             @if($alignment->is('bottom'))
                 x-transition:enter-start="translate-y-12 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             @elseif($alignment->is('top'))
                 x-transition:enter-start="-translate-y-12 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             @else
                 x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             @endif
             x-transition:leave-end="opacity-0 scale-90"
             @class([
                 'relative duration-300 transform transition ease-in-out max-w-xs w-full pointer-events-auto',
                 'text-center' => $position->is('center')
             ])
             :class="toast.select({
                 error: 'bg-red-500 dark:bg-red-700',
                 info: 'bg-blue-500 dark:bg-blue-700',
                 success: 'bg-green-500 dark:bg-green-700',
                 warning: 'bg-yellow-500 dark:bg-yellow-700'
             })"
             class="shadow-lg overflow-hidden"
             style="border-radius: 0.70rem;">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0 mr-3">
                    <div class="p-2 rounded-full bg-white bg-opacity-25 dark:bg-opacity-20">
                        <svg x-show="toast.type === 'error'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <svg x-show="toast.type === 'info'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <svg x-show="toast.type === 'success'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <svg x-show="toast.type === 'warning'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
                <div class="flex-1 pr-6">
                    <p x-text="toast.message"
                       class="text-sm font-medium text-white"></p>
                </div>
                @if($closeable)
                    <div class="absolute top-2 right-2">
                        <button @click="toast.dispose()"
                                class="text-white text-opacity-75 hover:text-opacity-100 focus:outline-none focus:text-opacity-100 transition-opacity duration-200"
                                aria-label="@lang('close')">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
            <div class="h-1 w-full bg-white bg-opacity-20 dark:bg-opacity-15 overflow-hidden">
                <div class="h-full bg-white bg-opacity-40 dark:bg-opacity-30 animate-shrink"></div>
            </div>
        </div>
    </template>
</div>

<style>
    @keyframes shrink {
        from { width: 100%; }
        to { width: 0%; }
    }
    .animate-shrink {
        animation: shrink 3000ms linear forwards;
    }
</style>
