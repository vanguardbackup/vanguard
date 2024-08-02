<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Tables;

use App\Models\NotificationStream;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the display of notification streams in a table format.
 *
 * This component handles the rendering and pagination of notification streams
 * for the authenticated user.
 */
class IndexTable extends Component
{
    /**
     * Render the notification streams index table.
     *
     * Fetches and paginates notification streams for the authenticated user.
     */
    public function render(): View
    {
        $notificationStreams = NotificationStream::where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'notification-streams');

        return view('livewire.notification-streams.tables.index-table', [
            'notificationStreams' => $notificationStreams,
        ]);
    }
}
