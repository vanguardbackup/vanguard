@props(['link' => null, 'title' => null])

<a href="{{ $link }}" title="{{ $title }}"
    {{ $attributes->merge(['class' => "block px-4 py-4 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/40"]) }}>
    {{ $slot }}
</a>
