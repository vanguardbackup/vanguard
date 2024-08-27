@props(['pageTitle' => __('Account'), 'action' => null])
@section('title', $pageTitle)
<x-app-layout>
    <x-slot name="header">
        {{ $pageTitle }}
    </x-slot>
    <div class="pb-8 md:pb-16">
        <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
            <nav class="mt-3 w-full shrink-0 md:mt-0 lg:w-64 xl:w-72">
                <x-account-sidebar />
            </nav>
            <main class="min-w-0 flex-1">
                <x-slot name="action">
                    {{ $action }}
                </x-slot>
                {{ $slot }}
            </main>
        </div>
    </div>
</x-app-layout>
