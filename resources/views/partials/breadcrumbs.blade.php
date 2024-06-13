<nav class="bg-white p-3 text-[0.90rem] border-b border-t border-gray-100 mx-auto">
    <div class="flex justify-center">
        <div class="mx-auto max-w-full">
            <ol class="list-reset flex text-gray-700">
                @foreach ($breadcrumbs as $breadcrumb)
                    @if (!is_null($breadcrumb->url) && !$loop->last)
                        <li class="flex items-center">
                            <a href="{{ $breadcrumb->url }}" wire:navigate class="text-gray-700 font-medium">
                                {{ $breadcrumb->title }}
                            </a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mx-2" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </li>
                    @else
                        <li class="flex items-center text-gray-800 font-semibold">
                            {{ $breadcrumb->title }}
                        </li>
                    @endif
                @endforeach
            </ol>
        </div>
    </div>
</nav>
