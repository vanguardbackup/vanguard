<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public function render(): View
    {
        $users = User::paginate(15);

        return view('livewire.admin.user-table', ['users' => $users]);
    }
}
