<?php

declare(strict_types=1);

namespace App\View\Components;

use Override;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * AccountSidebar Component
 *
 * This component represents the sidebar for the account section of the application.
 * It renders a view that contains navigation links or other account-related information.
 */
class AccountSidebar extends Component
{
    /**
     * Render the component.
     *
     * @return View The view instance for the account sidebar component
     */
    #[Override]
    public function render(): View
    {
        return view('account.partials.sidebar');
    }
}
