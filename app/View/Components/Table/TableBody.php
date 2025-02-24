<?php

declare(strict_types=1);

namespace App\View\Components\Table;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Override;

/**
 * TableBody Component
 *
 * This component represents the body of a table in the application's UI.
 * It renders a view that contains the main content rows of a table.
 */
class TableBody extends Component
{
    /**
     * Render the component.
     *
     * @return View The view instance for the table body component
     */
    #[Override]
    public function render(): View
    {
        return view('components.table.table-body');
    }
}
