<div>
    <x-table.table-row>
        <!-- Label column -->
        <div class="col-span-3">
            <p class="font-medium text-gray-900 dark:text-gray-100">
                {{ $script->label }}
            </p>
        </div>

        <!-- Status column -->
        <div class="col-span-3">
            @if ($script->wasSucessful())
                <span
                    class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-800 dark:text-green-100"
                >
                    <div class="mr-1.5 h-2 w-2 rounded-full bg-green-500"></div>
                    {{ __('Success') }}
                </span>
            @elseif (empty($script->output))
                <span
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                >
                    <div class="mr-1.5 h-2 w-2 rounded-full bg-gray-500"></div>
                    {{ __('Not Run') }}
                </span>
            @else
                <span
                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-100"
                >
                    <div class="mr-1.5 h-2 w-2 rounded-full bg-red-500"></div>
                    {{ __('Failed') }}
                </span>
            @endif
        </div>

        <!-- Type column -->
        <div class="col-span-3">
            <span class="block truncate text-sm text-gray-800 dark:text-gray-200">
                {{ ucfirst($script->type) }}
            </span>
        </div>

        <!-- Actions column -->
        <div class="col-span-3 flex justify-center space-x-2">
            <x-secondary-button
                iconOnly
                x-on:click="$dispatch('open-modal', 'view-script-output-{{ $script->id }}')"
                title="{{ __('View Script Output') }}"
            >
                <span class="sr-only">
                    {{ __('View Script Output') }}
                </span>
                <x-hugeicons-eye class="h-4 w-4" />
            </x-secondary-button>
            @livewire('scripts.delete-script-button', ['script' => $script])
        </div>
    </x-table.table-row>

    <!-- Script Output Modal -->
    <x-modal name="view-script-output-{{ $script->id }}" :key="'script-output-modal-' . $script->id" focusable>
        <x-slot name="title">
            {{ __('Script Output: :label', ['label' => $script->label]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('Output from the last execution of this script.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-computer</x-slot>
        <div class="max-h-96 overflow-y-auto">
            <div class="overflow-hidden bg-gray-900 shadow sm:rounded-lg">
                <div class="p-4">
                    @if (empty($script->output))
                        <p class="text-sm text-gray-400">{{ __('No output available.') }}</p>
                    @else
                        <pre class="overflow-x-auto whitespace-pre-wrap break-words font-mono text-sm text-gray-300">
{{ $script->output }}</pre
                        >
                    @endif
                </div>
            </div>
        </div>
        <div class="mt-6">
            <div class="flex justify-between space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="w-full justify-center">
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
