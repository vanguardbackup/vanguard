<?php

declare(strict_types=1);

namespace App\View\Components\Table;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * TableWrapper Component
 *
 * This component represents a wrapper for a table in the application's UI.
 * It encapsulates the table's title, description, and an optional action,
 * providing a consistent structure for table presentation.
 */
class TableWrapper extends Component
{
    /**
     * Create a new component instance.
     *
     * @param  string  $title  The title of the table
     * @param  string  $description  The description of the table
     * @param  string|View|null  $action  An optional action for the table (can be a string, View, or null)
     */
    public function __construct(
        public string $title,
        public string $description,
        public string|View|null $action = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return Factory|\Illuminate\Contracts\View\View|Application The view instance for the table wrapper
     */
    public function render(): Factory|\Illuminate\Contracts\View\View|Application
    {
        return view('components.table.table-wrapper');
    }
}
