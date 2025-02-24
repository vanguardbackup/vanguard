<?php

declare(strict_types=1);

namespace App\View\Components;

use Override;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * GuestLayout Component
 *
 * This component represents the layout for guest (unauthenticated) users of the application.
 * It provides a consistent structure and appearance for pages that are accessible
 * to users who are not logged in, such as login, registration, or public-facing pages.
 */
class GuestLayout extends Component
{
    /**
     * Render the guest layout component.
     *
     * @return View The view instance for the guest layout
     */
    #[Override]
    public function render(): View
    {
        return view('layouts.guest');
    }
}
