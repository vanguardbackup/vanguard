<div>
    <x-table.table-wrapper
        title="{{ __('Users') }}"
        description="{{ __('A list of users that have created accounts on this instance.') }}"
    >
        <x-slot name="icon">
            <x-hugeicons-user-multiple-02 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
        </x-slot>
        <x-table.table-header>
            <div class="col-span-3">{{ __('Name & Email') }}</div>
            <div class="col-span-3">{{ __('Status') }}</div>
            <div class="col-span-3">{{ __('Type') }}</div>
            <div class="col-span-3">{{ __('Actions') }}</div>
        </x-table.table-header>
        <x-table.table-body>
            @foreach ($users as $user)
                @livewire('admin.user.user-row', ['user' => $user], key($user->id))
            @endforeach
        </x-table.table-body>
    </x-table.table-wrapper>
    <div class="mt-4 flex justify-end">
        {{ $users->links() }}
    </div>
</div>
