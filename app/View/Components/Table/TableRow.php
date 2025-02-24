<?php

declare(strict_types=1);

namespace App\View\Components\Table;

use Override;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * TableRow Component
 *
 * This component represents a row within a table in the application's UI.
 * It renders a view that typically contains the cells or columns of a single table row.
 */
class TableRow extends Component
{
    /**
     * Create a new component instance.
     *
     * This constructor is currently empty, but it's available for any
     * initialization logic that might be needed in the future, such as
     * accepting row data or styling options.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string The view instance, a Closure that creates a view, or a string
     */
    #[Override]
    public function render(): View|Closure|string
    {
        return view('components.table.table-row');
    }
}
