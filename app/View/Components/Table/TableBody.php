<?php

declare(strict_types=1);

namespace App\View\Components\Table;

use Illuminate\View\Component;

class TableBody extends Component
{
    public function render()
    {
        return view('components.table.table-body');
    }
}
