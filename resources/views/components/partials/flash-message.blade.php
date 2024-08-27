<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition duration-300 ease-out"
    x-transition:enter-start="-translate-y-2 transform opacity-0"
    x-transition:enter-end="translate-y-0 transform opacity-100"
    x-transition:leave="transition duration-300 ease-in"
    x-transition:leave-start="translate-y-0 transform opacity-100"
    x-transition:leave-end="-translate-y-2 transform opacity-0"
    class="{{ $alertClasses() }} rounded-r-lg border-l-4 p-4 shadow-md"
    role="alert"
    style="background: linear-gradient(to right, {{ $gradientStart() }}, {{ $gradientEnd() }})"
>
    <div class="flex items-center">
        <div class="flex-shrink-0">
            @svg($icon(), 'h-6 w-6')
        </div>
        <div class="ml-3 flex-grow">
            <p class="text-sm font-medium">{{ $message }}</p>
        </div>
        @if ($dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        @click="show = false"
                        type="button"
                        class="{{ $buttonClasses() }} inline-flex rounded-full p-1.5 hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    >
                        <span class="sr-only">Dismiss</span>
                        @svg('hugeicons-cancel-01', 'h-5 w-5')
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
