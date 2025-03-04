@php
    $defaultClasses =
        'text-gray-500 transition-colors duration-200 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400';
    $activeClasses = 'font-medium text-gray-800 dark:text-white';
    $separatorClasses = 'mx-2 text-gray-300 dark:text-gray-600';
@endphp

<nav aria-label="Breadcrumb" class="py-3">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <ol class="flex flex-wrap items-center text-sm">
            @foreach ($breadcrumbs as $breadcrumb)
                <li class="{{ $loop->last ? '' : 'whitespace-nowrap' }} flex items-center">
                    @if (! is_null($breadcrumb->url) && ! $loop->last)
                        <a
                            href="{{ $breadcrumb->url }}"
                            wire:navigate
                            class="{{ $defaultClasses }} group flex items-center"
                        >
                            @if ($loop->first)
                                <span class="sr-only">Home</span>
                                @svg('hugeicons-home-05', 'h-4 w-4')
                            @else
                                <span class="group-hover:underline">
                                    {{ $breadcrumb->title }}
                                </span>
                            @endif
                        </a>
                    @else
                        <span
                            class="{{ $activeClasses }} truncate"
                            aria-current="page"
                            title="{{ $breadcrumb->title }}"
                        >
                            {{ $breadcrumb->title }}
                        </span>
                    @endif

                    @if (! $loop->last)
                        <svg
                            class="{{ $separatorClasses }} h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            width="24"
                            height="24"
                            fill="none"
                        >
                            <path
                                d="M9.00005 6C9.00005 6 15 10.4189 15 12C15 13.5812 9 18 9 18"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</nav>
