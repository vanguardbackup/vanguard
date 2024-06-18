@props(['name', 'value' => null, 'label' => null])

<div class="form-check">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="my-1.5 rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:focus:ring-offset-gray-800" id="{{ $name }}" {{ $attributes }}>
    @if ($label)
        <label class="text-gray-700 dark:text-gray-200 text-sm ms-1" for="{{ $name }}">
            {{ $label }}
        </label>
    @endif
</div>
