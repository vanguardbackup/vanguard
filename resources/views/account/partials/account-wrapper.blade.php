@props(['pageTitle' => __('Account'), 'action' => null])
@section('title', $pageTitle)
<x-app-layout>
    <x-slot name="header">
        {{ $pageTitle }}
    </x-slot>
    <div class="pb-8 md:pb-16">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            <nav class="w-full lg:w-64 xl:w-72 shrink-0 mt-3 md:mt-0">
                <x-account-sidebar/>
            </nav>
            <main class="flex-1 min-w-0">
                <x-slot name="action">
                    {{ $action }}
                </x-slot>
                {{ $slot }}
            </main>
        </div>
    </div>
</x-app-layout>
