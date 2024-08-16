<div x-data="{ show: true }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform -translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform -translate-y-2"
     class="border-l-4 p-4 rounded-r-lg shadow-md {{ $alertClasses() }}"
     role="alert"
     style="background: linear-gradient(to right, {{ $gradientStart() }}, {{ $gradientEnd() }});">
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
                    <button @click="show = false" type="button" class="inline-flex rounded-full p-1.5 hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $buttonClasses() }}">
                        <span class="sr-only">Dismiss</span>
                        @svg('heroicon-o-x-mark', 'h-5 w-5')
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
