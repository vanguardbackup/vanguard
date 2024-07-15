@props(['name', 'value' => false, 'label' => null, 'model' => null, 'live' => false])

<div x-data="{ isOn: @entangle($model){{ $live ? '.live' : '' }} }" class="flex items-center">
    <div class="relative">
        <button type="button"
                x-on:click="isOn = !isOn"
                :class="{ 'bg-gray-950 dark:bg-white': isOn, 'bg-gray-200 dark:bg-gray-600': !isOn }"
                class="relative inline-flex flex-shrink-0 h-7 w-12 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800"
                role="switch"
                :aria-checked="isOn.toString()"
                :aria-labelledby="$id('toggle-label')"
        >
            <span class="sr-only">{{ $label }}</span>
            <span
                :class="{ 'translate-x-5': isOn, 'translate-x-0': !isOn }"
                class="pointer-events-none relative inline-block h-6 w-6 rounded-full bg-white dark:bg-gray-950 shadow transform ring-0 transition ease-in-out duration-200"
            >
                <span
                    :class="{ 'opacity-0 ease-out duration-100': isOn, 'opacity-100 ease-in duration-200': !isOn }"
                    class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity"
                    aria-hidden="true"
                >
                    <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 12 12">
                        <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span
                    :class="{ 'opacity-100 ease-in duration-200': isOn, 'opacity-0 ease-out duration-100': !isOn }"
                    class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity"
                    aria-hidden="true"
                >
                    <svg class="h-3.5 w-3.5 text-gray-950 dark:text-white" fill="currentColor" viewBox="0 0 12 12">
                        <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                    </svg>
                </span>
            </span>
        </button>
        <!-- Invisible touch target overlay -->
        <div class="absolute inset-0 -m-4 cursor-pointer" x-on:click="isOn = !isOn"></div>
    </div>

    @if ($label)
        <label :id="$id('toggle-label')" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer" x-on:click="isOn = !isOn">
            {{ $label }}
        </label>
    @endif

    <input type="hidden" name="{{ $name }}" x-bind:value="isOn" />
</div>
