<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use Illuminate\View\View;
use Livewire\Component;

class UserPage extends Component
{
    public function render(): View
    {
        $user = request()->user();

        if (! $user || ! $user->isAdmin()) {
            abort(404);
        }

        return view('livewire.admin.user-page');
    }
}
