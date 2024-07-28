@php
    $defaultClasses = 'text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white transition-all duration-200';
    $activeClasses = 'text-gray-700 dark:text-gray-300 font-medium';
    $separatorClasses = 'mx-2 text-gray-400 dark:text-gray-500';
@endphp

<nav aria-label="Breadcrumb" class="bg-white dark:bg-gray-900 py-4">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-center">
        <ol class="flex flex-wrap items-center justify-center text-sm sm:text-base">
            @foreach ($breadcrumbs as $breadcrumb)
                <li class="flex items-center">
                    @if (!is_null($breadcrumb->url) && !$loop->last)
                        <a href="{{ $breadcrumb->url }}" wire:navigate class="{{ $defaultClasses }} group">
                            @if ($loop->first)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                                <span class="sr-only">Home</span>
                            @else
                                <span class="group-hover:underline">{{ $breadcrumb->title }}</span>
                            @endif
                        </a>
                    @else
                        <span class="{{ $activeClasses }}" aria-current="page">
                            {{ $breadcrumb->title }}
                        </span>
                    @endif

                    @if (!$loop->last)
                        <svg class="{{ $separatorClasses }} w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</nav>
