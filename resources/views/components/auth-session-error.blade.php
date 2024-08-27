@props([
    'loginError',
])

@if ($loginError)
    <div
        {{ $attributes->merge(['class' => 'font-medium text-sm text-red-600 dark:text-red-400 py-2 px-2 border border-red-200 bg-red-50 rounded-lg text-center']) }}
    >
        @svg('hugeicons-alert-02', 'me-2 inline-block h-5 w-5')
        {{ $loginError }}
    </div>
@endif
