<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * MinimalLayout Component
 *
 * This component represents a simplified layout for focused authentication pages,
 * particularly designed for the two-factor authentication process. It provides
 * a clean, distraction-free structure that retains essential styling and
 * functionality while removing complex navigation and footers.
 */
class MinimalLayout extends Component
{
    /**
     * Render the minimal layout component.
     *
     * This method returns a view instance for the minimal layout, which is
     * optimized for authentication processes like two-factor verification.
     *
     * @return View The view instance for the minimal layout
     */
    public function render(): View
    {
        return view('layouts.minimal');
    }
}
