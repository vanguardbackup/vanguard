@props(['centered' => false, 'iconOnly' => false])

<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center ' . ($iconOnly ? 'px-3 py-2' : 'px-7 py-2.5') . ' bg-gray-100/75 dark:bg-gray-800 dark:border-gray-600 border border-gray-400/25 rounded-[0.70rem] font-semibold text-sm text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-gray-800 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150' . ($centered ? ' justify-center w-full' : '')]) }}>
    {{ $slot }}
</button>
