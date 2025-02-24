<?php

declare(strict_types=1);

namespace App\View\Components\Partials;

use Override;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FlashMessage extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        /**
         * The message to display.
         */
        public string $message,
        /**
         * The type of the message (success, warning, error, info).
         */
        public string $type = 'info',
        /**
         * Whether the message can be dismissed.
         */
        public bool $dismissible = true
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    #[Override]
    public function render(): View|string|Closure|null
    {
        return view('components.partials.flash-message');
    }

    /**
     * Get the CSS classes for the alert based on the message type.
     */
    public function alertClasses(): string
    {
        return match ($this->type) {
            'success' => 'text-emerald-900 dark:text-emerald-100',
            'error' => 'text-rose-900 dark:text-rose-100',
            'warning' => 'text-amber-900 dark:text-amber-100',
            'info' => 'text-sky-900 dark:text-sky-100',
            default => 'text-gray-900 dark:text-gray-100',
        };
    }

    /**
     * Get the background color class based on the message type.
     */
    public function bgClass(): string
    {
        return match ($this->type) {
            'success' => 'bg-emerald-50 dark:bg-emerald-900/30',
            'error' => 'bg-rose-50 dark:bg-rose-900/30',
            'warning' => 'bg-amber-50 dark:bg-amber-900/30',
            'info' => 'bg-sky-50 dark:bg-sky-900/30',
            default => 'bg-gray-50 dark:bg-gray-900/30',
        };
    }

    /**
     * Get the icon color class based on the message type.
     */
    public function iconColorClass(): string
    {
        return match ($this->type) {
            'success' => 'text-emerald-500 dark:text-emerald-400',
            'error' => 'text-rose-500 dark:text-rose-400',
            'warning' => 'text-amber-500 dark:text-amber-400',
            'info' => 'text-sky-500 dark:text-sky-400',
            default => 'text-gray-500 dark:text-gray-400',
        };
    }

    /**
     * Get the CSS classes for the dismiss button based on the message type.
     */
    public function buttonClasses(): string
    {
        return match ($this->type) {
            'success' => 'hover:bg-emerald-100 dark:hover:bg-emerald-800 focus:ring-emerald-400',
            'error' => 'hover:bg-rose-100 dark:hover:bg-rose-800 focus:ring-rose-400',
            'warning' => 'hover:bg-amber-100 dark:hover:bg-amber-800 focus:ring-amber-400',
            'info' => 'hover:bg-sky-100 dark:hover:bg-sky-800 focus:ring-sky-400',
            default => 'hover:bg-gray-100 dark:hover:bg-gray-800 focus:ring-gray-400',
        };
    }

    /**
     * Get the icon for the alert based on the message type.
     */
    public function icon(): string
    {
        return match ($this->type) {
            'success' => 'hugeicons-checkmark-circle-02',
            'warning' => 'hugeicons-alert-02',
            'error' => 'hugeicons-cancel-circle',
            default => 'hugeicons-information-circle',
        };
    }
}
