<?php

declare(strict_types=1);

namespace App\View\Components;

use Override;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * AccountWrapper Component
 *
 * This component serves as a wrapper for the account section of the application.
 * It renders a view that likely provides a consistent layout or structure for account-related pages.
 */
class AccountWrapper extends Component
{
    /**
     * Render the component.
     *
     * @return View The view instance for the account wrapper component
     */
    #[Override]
    public function render(): View
    {
        return view('account.partials.account-wrapper');
    }
}
