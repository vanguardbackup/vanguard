@props(['href' => '#'])

<a
    href="{{ $href }}"
    {{
        $attributes->merge([
            'class' => 'block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150 ease-in-out first:rounded-t-[0.60rem] last:rounded-b-[0.60rem]',
            'role' => 'menuitem',
            'tabindex' => '-1',
        ])
    }}
>
    {{ $slot }}
</a>
