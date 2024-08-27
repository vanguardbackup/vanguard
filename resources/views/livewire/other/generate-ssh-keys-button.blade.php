<div class="w-full sm:inline-flex">
    <button
        wire:click="generateKeys"
        class="w-full rounded-full border border-white/25 bg-white/10 px-6 py-2 text-sm font-medium text-white backdrop-blur-sm transition-all duration-150 ease-out hover:-translate-y-0.5 hover:bg-white/20 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-red-600 sm:w-auto"
    >
        @svg('hugeicons-play', ['class' => 'mr-2 inline h-4 w-4'])
        {{ __('Generate SSH Keys') }}
    </button>
</div>
