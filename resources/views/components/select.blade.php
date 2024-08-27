@props([
    'disabled' => false,
    'name',
])

@php
    $validationClass = $errors->has($name) ? 'border-red-600' : 'border-gray-900/20 dark:border-gray-700';
@endphp

<div class="relative">
    <select
        {{ $disabled ? 'disabled' : '' }}
        {!!
            $attributes->merge([
                'class' => "
                                                                                                                                                                                                                                                    {$validationClass}
                                                                                                                                                                                                                                                    mt-2
                                                                                                                                                                                                                                                    dark:bg-gray-700/40
                                                                                                                                                                                                                                                    dark:text-gray-50
                                                                                                                                                                                                                                                    bg-[#FDFDFD]
                                                                                                                                                                                                                                                    focus:border-primary-900/30
                                                                                                                                                                                                                                                    focus:ring-primary-500
                                                                                                                                                                                                                                                    rounded-[0.55rem]
                                                                                                                                                                                                                                                    h-12
                                                                                                                                                                                                                                                    shadow-none
                                                                                                                                                                                                                                                    w-full
                                                                                                                                                                                                                                                    appearance-none
                                                                                                                                                                                                                                                    pr-10
                                                                                                                                                                                                                                                    pl-4
                                                                                                                                                                                                                                                ",
                'style' => "background-image: url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e\"); background-position: right 0.5rem center; background-size: 1.5em 1.5em; background-repeat: no-repeat;",
            ])
        !!}
    >
        {{ $slot }}
    </select>

    @if ($errors->has($name))
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-8">
            @svg('hugeicons-alert-02', ['class' => 'h-5 w-5 text-red-600'])
        </div>
    @endif
</div>
