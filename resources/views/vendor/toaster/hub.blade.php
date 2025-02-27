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
        <!-- Main toast container -->
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
             class="shadow-lg"
             style="overflow: hidden; border-radius: 0.70rem;">

            <!-- Colored background div with content -->
            <div :class="toast.select({
                    error: 'bg-red-500 dark:bg-red-700',
                    info: 'bg-blue-500 dark:bg-blue-700',
                    success: 'bg-green-500 dark:bg-green-700',
                    warning: 'bg-yellow-500 dark:bg-yellow-700'
                })"
                 class="relative">

                <!-- Toast content -->
                <div class="flex items-center p-4">
                    <div class="flex-shrink-0 mr-3">
                        <div class="p-2 rounded-full bg-white bg-opacity-25 dark:bg-opacity-20">
                            <svg x-show="toast.type === 'error'" class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="#000000" fill="none">
                                <path d="M14.9994 15L9 9M9.00064 15L15 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z" stroke="currentColor" stroke-width="1.5" />
                            </svg>

                            <svg x-show="toast.type === 'info'" class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="#000000" fill="none">
                                <path d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z" stroke="currentColor" stroke-width="1.5" />
                                <path d="M12.2422 17V12C12.2422 11.5286 12.2422 11.2929 12.0957 11.1464C11.9493 11 11.7136 11 11.2422 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M11.992 8H12.001" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <svg x-show="toast.type === 'success'" class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="#000000" fill="none">
                                <path d="M5 14.5C5 14.5 6.5 14.5 8.5 18C8.5 18 14.0588 8.83333 19 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <svg x-show="toast.type === 'warning'" class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="#000000" fill="none">
                                <path d="M5.32171 9.6829C7.73539 5.41196 8.94222 3.27648 10.5983 2.72678C11.5093 2.42437 12.4907 2.42437 13.4017 2.72678C15.0578 3.27648 16.2646 5.41196 18.6783 9.6829C21.092 13.9538 22.2988 16.0893 21.9368 17.8293C21.7376 18.7866 21.2469 19.6548 20.535 20.3097C19.241 21.5 16.8274 21.5 12 21.5C7.17265 21.5 4.75897 21.5 3.46496 20.3097C2.75308 19.6548 2.26239 18.7866 2.06322 17.8293C1.70119 16.0893 2.90803 13.9538 5.32171 9.6829Z" stroke="currentColor" stroke-width="1.5" />
                                <path d="M11.992 16H12.001" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12 13L12 8.99997" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
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
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="#000000" fill="none">
                                    <path d="M19.0005 4.99988L5.00049 18.9999M5.00049 4.99988L19.0005 18.9999" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Progress bar - completely within the colored background -->
                <div class="h-1 bg-white bg-opacity-20 dark:bg-opacity-15">
                    <div class="h-1 bg-white bg-opacity-40 dark:bg-opacity-30 animate-shrink"></div>
                </div>
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
