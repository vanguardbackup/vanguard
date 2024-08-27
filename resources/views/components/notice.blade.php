@props([
    "type" => "info",
    "title" => null,
    "icon" => null,
    "showIf" => null,
    "text" => null,
    "class" => "",
])

@php
    $colors = [
        "info" => [
            "bg" => "bg-sky-50 dark:bg-sky-900/30",
            "text" => "text-sky-900 dark:text-sky-100",
            "icon" => "text-sky-500 dark:text-sky-400",
            "body" => "text-sky-800 dark:text-sky-200",
        ],
        "warning" => [
            "bg" => "bg-amber-50 dark:bg-amber-900/30",
            "text" => "text-amber-900 dark:text-amber-100",
            "icon" => "text-amber-500 dark:text-amber-400",
            "body" => "text-amber-800 dark:text-amber-200",
        ],
        "success" => [
            "bg" => "bg-emerald-50 dark:bg-emerald-900/30",
            "text" => "text-emerald-900 dark:text-emerald-100",
            "icon" => "text-emerald-500 dark:text-emerald-400",
            "body" => "text-emerald-800 dark:text-emerald-200",
        ],
        "error" => [
            "bg" => "bg-rose-50 dark:bg-rose-900/30",
            "text" => "text-rose-900 dark:text-rose-100",
            "icon" => "text-rose-500 dark:text-rose-400",
            "body" => "text-rose-800 dark:text-rose-200",
        ],
    ];

    $selectedColors = $colors[$type] ?? $colors["info"];

    if (! $icon) {
        $icon = match ($type) {
            "warning" => '<path d="M5.32171 9.68293C7.73539 5.41199 8.94222 3.27651 10.5983 2.72681C11.5093 2.4244 12.4907 2.4244 13.4017 2.72681C15.0578 3.27651 16.2646 5.41199 18.6783 9.68293C21.092 13.9539 22.2988 16.0893 21.9368 17.8293C21.7376 18.7866 21.2469 19.6549 20.535 20.3097C19.241 21.5 16.8274 21.5 12 21.5C7.17265 21.5 4.75897 21.5 3.46496 20.3097C2.75308 19.6549 2.26239 18.7866 2.06322 17.8293C1.70119 16.0893 2.90803 13.9539 5.32171 9.68293Z" stroke="currentColor"></path><path d="M11.992 16H12.001" stroke="currentColor"></path><path d="M12 13L12 9" stroke="currentColor"></path>',
            "info" => '<path d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z" stroke="currentColor" stroke-width="1.5" /> <path d="M12.2422 17V12C12.2422 11.5286 12.2422 11.2929 12.0957 11.1464C11.9493 11 11.7136 11 11.2422 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /> <path d="M11.992 8H12.001" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />',
            "success" => '<path d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z" stroke="currentColor" stroke-width="1.5" /> <path d="M8 12.75C8 12.75 9.6 13.6625 10.4 15C10.4 15 12.8 9.75 16 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />',
            "error" => '<path d="M14.9994 15L9 9M9.00064 15L15 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /><path d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z" stroke="currentColor" stroke-width="1.5" />',
            default => '<path d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z" stroke="currentColor" stroke-width="1.5" /> <path d="M12.2422 17V12C12.2422 11.5286 12.2422 11.2929 12.0957 11.1464C11.9493 11 11.7136 11 11.2422 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /> <path d="M11.992 8H12.001" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />',
        };
    }
@endphp

<div
    @if ($showIf)
        x-show="{{ $showIf }}"
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="scale-95 transform opacity-0"
        x-transition:enter-end="scale-100 transform opacity-100"
    @endif
    @class([
        "rounded-[0.70rem] border-none p-4 ring-1 ring-black ring-opacity-5 backdrop-blur-sm",
        $class,
    ])
>
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <svg
                class="{{ $selectedColors["icon"] }} h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke="currentColor"
            >
                {!! $icon !!}
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            @if ($title)
                <h3 class="{{ $selectedColors["text"] }} text-sm font-medium">
                    {{ $title }}
                </h3>
            @endif

            <div class="{{ $selectedColors["body"] }} mt-1 text-sm">
                @if ($text)
                    {{ $text }}
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>
    </div>
</div>
