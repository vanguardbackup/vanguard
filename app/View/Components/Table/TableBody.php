<?php

namespace App\View\Components\Table;

use Illuminate\View\Component;

class TableBody extends Component
{
    public function __construct() {}

    public function render()
    {
        return view('components.table.table-body');
    }
}
