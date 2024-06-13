@props(['disabled' => false, 'name'])

@php
    $validationClass = $errors->has($name) ? 'border-red-600' : 'border-gray-900/20 dark:border-gray-700';
@endphp

<div class="relative">
    <input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => "{$validationClass} mt-1 bg-[#FDFDFD] dark:bg-gray-700/40 dark:text-gray-50 focus:border-primary-900/30 focus:ring-primary-500 rounded-[0.55rem] h-12 shadow-none"]) !!}>

    @if ($errors->has($name))
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            @svg('heroicon-o-exclamation-triangle', ['class' => 'w-5 h-5 ml-2 text-red-600'])
        </div>
    @endif
</div>
