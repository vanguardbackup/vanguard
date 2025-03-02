<?php

declare(strict_types=1);

namespace App\Livewire\Scripts\Tables;

use App\Models\Script;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Livewire component for displaying a single script item in a list.
 *
 * This component handles the rendering of an individual script in the scripts index.
 */
class IndexItem extends Component
{
    /** @var Script The script instance to be displayed. */
    public Script $script;

    /**
     * Initialize the component with the given script.
     */
    public function mount(Script $script): void
    {
        $this->script = $script;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.scripts.tables.index-item');
    }
}
