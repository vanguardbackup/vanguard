@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between space-x-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-200 border border-gray-300 cursor-default rounded-md dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600">
                &laquo; {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:border-blue-300 transition ease-in-out duration-150 dark:text-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-blue-700 dark:focus:border-blue-700">
                &laquo; {!! __('pagination.previous') !!}
            </a>
        @endif

        <div class="flex space-x-1">
            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md cursor-default dark:text-gray-300 dark:bg-gray-800 dark:border-gray-600">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-700 border border-gray-300 cursor-default rounded-md dark:bg-gray-500 dark:border-gray-600">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:border-blue-300 transition ease-in-out duration-150 dark:text-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-blue-700 dark:focus:border-blue-700">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:border-blue-300 transition ease-in-out duration-150 dark:text-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-blue-700 dark:focus:border-blue-700">
                {!! __('pagination.next') !!} &raquo;
            </a>
        @else
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-200 border border-gray-300 cursor-default rounded-md dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600">
                {!! __('pagination.next') !!} &raquo;
            </span>
        @endif
    </nav>
@endif
