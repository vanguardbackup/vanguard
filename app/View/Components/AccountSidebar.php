<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AccountSidebar extends Component
{
    public function render(): View
    {
        return view('account.partials.sidebar');
    }
}
