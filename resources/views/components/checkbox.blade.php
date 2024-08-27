@props([
    'id',
    'name',
    'value' => null,
    'label' => null,
])

<div class="form-check flex items-center">
    <input
        type="checkbox"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        class="my-1.5 h-5 w-5 rounded border-gray-300 text-primary-800 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-primary-900 dark:focus:ring-offset-gray-900"
        {{ $attributes }}
    />
    @if ($label)
        <label class="ml-2 cursor-pointer text-sm text-gray-700 dark:text-gray-200" for="{{ $id }}">
            {{ $label }}
        </label>
    @endif
</div>
