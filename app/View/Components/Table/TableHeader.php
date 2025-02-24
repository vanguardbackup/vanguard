<?php

declare(strict_types=1);

namespace App\View\Components\Table;

use Override;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * TableHeader Component
 *
 * This component represents the header of a table in the application's UI.
 * It renders a view that typically contains the column titles or header cells of a table.
 */
class TableHeader extends Component
{
    /**
     * Create a new component instance.
     *
     * This constructor is currently empty, but it's available for any
     * initialization logic that might be needed in the future.
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
        return view('components.table.table-header');
    }
}
