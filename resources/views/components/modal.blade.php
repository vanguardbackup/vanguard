@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'icon' => null,
    'description' => null,
])

@php
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
        autofocus() { const focusable = $el.querySelector('[autofocus]'); if (focusable) focusable.focus() },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            setTimeout(() => autofocus(), 100);
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-2 py-4 sm:px-4 sm:py-6 z-50 flex items-center justify-center"
    style="display: none;"
    x-cloak
>
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75 backdrop-blur-sm"></div>
    </div>

    <div
        x-show="show"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-[0.70rem] overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-[-100%]"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-[-100%]"
        @click.away="show = false"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="$id('modal-title')"
    >
        <div class="px-6 py-5">
            <div class="flex items-center">
                @if ($icon)
                    <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                        @svg($icon, ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                    </div>
                @endif
                <div>
                    @if (isset($title))
                        <h3 :id="$id('modal-title')" class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $title }}
                        </h3>
                    @endif
                    @if ($description)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $description }}
                        </p>
                    @endif
                </div>
                <div class="ml-auto">
                    <button
                        @click="show = false"
                        class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800 rounded-md"
                        aria-label="{{ __('Close modal') }}"
                    >
                        <span class="sr-only">{{ __('Close') }}</span>
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-base px-6 py-5">
            {{ $slot }}
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .modal-content-enter {
        animation: modal-content-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes modal-content-in {
        0% { opacity: 0; transform: scale(0.9) translateY(-10px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
</style>
