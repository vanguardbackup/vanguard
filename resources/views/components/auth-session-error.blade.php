@props(['loginError'])

@if ($loginError)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-red-600 dark:text-red-400 py-2 px-2 border border-red-200 bg-red-50 rounded-lg text-center']) }}>
        @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 inline-block me-2')
        {{ $loginError }}
    </div>
@endif
