<div class="inline-flex">
    <button wire:click="generateKeys" class="ml-2 bg-red-400/65 hover:bg-red-400/90 p-2 px-6 text-sm rounded-[.70rem] focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 transition ease-in-out duration-150">
        @svg('heroicon-o-play', ['class' => 'h-4 -w-4 -mt-1 inline'])
        {{ __('Generate SSH Keys') }}
    </button>
</div>
