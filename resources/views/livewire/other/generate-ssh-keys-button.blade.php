<div class="w-full sm:inline-flex">
    <button
        wire:click="generateKeys"
        class="w-full sm:w-auto bg-white/10 hover:bg-white/20 backdrop-blur-sm px-6 py-2 text-sm font-medium text-white rounded-full border border-white/25 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-red-600 transition-all duration-150 ease-out hover:shadow-lg hover:-translate-y-0.5"
    >
        @svg('heroicon-o-play', ['class' => 'h-4 w-4 inline mr-2'])
        {{ __('Generate SSH Keys') }}
    </button>
</div>
