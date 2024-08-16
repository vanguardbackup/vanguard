<?php

declare(strict_types=1);

namespace App\View\Components\Partials;

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
            'success' => 'border-green-500 text-green-800',
            'error' => 'border-red-500 text-red-800',
            'warning' => 'border-yellow-500 text-yellow-800',
            'info' => 'border-blue-500 text-blue-800',
            default => 'border-gray-500 text-gray-800',
        };
    }

    /**
     * Get the starting color for the gradient based on the message type.
     */
    public function gradientStart(): string
    {
        return match ($this->type) {
            'success' => '#f0fdf4',
            'error' => '#fef2f2',
            'warning' => '#fffbeb',
            'info' => '#eff6ff',
            default => '#f9fafb',
        };
    }

    /**
     * Get the ending color for the gradient based on the message type.
     */
    public function gradientEnd(): string
    {
        return match ($this->type) {
            'success' => '#dcfce7',
            'error' => '#fee2e2',
            'warning' => '#fef3c7',
            'info' => '#dbeafe',
            default => '#f3f4f6',
        };
    }

    /**
     * Get the CSS classes for the dismiss button based on the message type.
     */
    public function buttonClasses(): string
    {
        return match ($this->type) {
            'success' => 'hover:bg-green-200 focus:ring-green-400',
            'error' => 'hover:bg-red-200 focus:ring-red-400',
            'warning' => 'hover:bg-yellow-200 focus:ring-yellow-400',
            'info' => 'hover:bg-blue-200 focus:ring-blue-400',
            default => 'hover:bg-gray-200 focus:ring-gray-400',
        };
    }

    /**
     * Get the icon for the alert based on the message type.
     */
    public function icon(): string
    {
        return match ($this->type) {
            'success' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'error' => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle',
        };
    }
}
