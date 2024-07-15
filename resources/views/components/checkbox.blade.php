@props(['id', 'name', 'value' => null, 'label' => null])

<div class="form-check flex items-center">
    <input
        type="checkbox"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        class="w-5 h-5 my-1.5 rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-800 shadow-sm focus:ring-primary-500 dark:focus:ring-primary-900 dark:focus:ring-offset-gray-900"
        {{ $attributes }}
    >
    @if ($label)
        <label class="text-gray-700 dark:text-gray-200 text-sm ml-2 cursor-pointer" for="{{ $id }}">
            {{ $label }}
        </label>
    @endif
</div>
