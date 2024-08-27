@props(['disabled' => false, 'name' => null])

@php
    $hasError = $name && $errors->has($name);
    $validationClass = $hasError ? 'border-red-600' : 'border-gray-900/20 dark:border-gray-700';
@endphp

<div class="relative">
    <textarea
        {{ $name ? "name={$name}" : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {!!
            $attributes->merge([
                'class' => "
                                                                                                                                                                                                                                                    {$validationClass}
                                                                                                                                                                                                                                                    dark:bg-gray-700/40
                                                                                                                                                                                                                                                    dark:text-gray-50
                                                                                                                                                                                                                                                    block
                                                                                                                                                                                                                                                    mt-2
                                                                                                                                                                                                                                                    w-full
                                                                                                                                                                                                                                                    bg-[#FDFDFD]
                                                                                                                                                                                                                                                    rounded-lg
                                                                                                                                                                                                                                                    focus:border-primary-900/30
                                                                                                                                                                                                                                                    focus:ring-primary-500
                                                                                                                                                                                                                                                    rounded-[0.55rem]
                                                                                                                                                                                                                                                    shadow-none
                                                                                                                                                                                                                                                    pr-10
                                                                                                                                                                                                                                                    p-4
                                                                                                                                                                                                                                                ",
            ])
        !!}
    >
{{ $slot }}</textarea
    >

    @if ($hasError)
        <div class="pointer-events-none absolute right-2 top-2 flex items-center">
            @svg('hugeicons-alert-02', ['class' => 'h-5 w-5 text-red-600'])
        </div>
    @endif
</div>
