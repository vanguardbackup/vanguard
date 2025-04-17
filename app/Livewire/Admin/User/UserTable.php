<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Override;

class UserTable extends Component
{
    use WithPagination;

    public function render(): View
    {
        $users = User::paginate(15);

        return view('livewire.admin.user-table', ['users' => $users]);
    }

    /**
     * Refresh the component data.
     */
    public function refreshData(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function getListeners(): array
    {
        return [
            'refreshUserTable' => 'refreshData',
        ];
    }
}
