<?php

declare(strict_types=1);

use App\Models\NotificationStream;

it('returns true if stream type is email', function (): void {
    $notificationStream = NotificationStream::factory()->email()->create();
    expect($notificationStream->isEmail())->toBeTrue();
});

it('returns false if the stream type is not email', function (): void {
    $notificationStream = NotificationStream::factory()->discord()->create();
    expect($notificationStream->isEmail())->toBeFalse();
});

it('returns true if stream type is discord', function (): void {
    $notificationStream = NotificationStream::factory()->discord()->create();
    expect($notificationStream->isDiscord())->toBeTrue();
});

it('returns false if the stream type is not discord', function (): void {
    $notificationStream = NotificationStream::factory()->email()->create();
    expect($notificationStream->isDiscord())->toBeFalse();
});

it('returns true if stream type is slack', function (): void {
    $notificationStream = NotificationStream::factory()->slack()->create();
    expect($notificationStream->isSlack())->toBeTrue();
});

it('returns false if the stream type is not slack', function (): void {
    $notificationStream = NotificationStream::factory()->email()->create();
    expect($notificationStream->isSlack())->toBeFalse();
});

it('returns true if stream type is teams', function (): void {
    $notificationStream = NotificationStream::factory()->teams()->create();
    expect($notificationStream->isTeams())->toBeTrue();
});

it('returns false if the stream type is not teams', function (): void {
    $notificationStream = NotificationStream::factory()->email()->create();
    expect($notificationStream->isTeams())->toBeFalse();
});

it('returns correct formatted type for email', function (): void {
    $notificationStream = NotificationStream::factory()->email()->create();
    expect($notificationStream->formatted_type)->toBe('Email');
});

it('returns correct formatted type for discord', function (): void {
    $notificationStream = NotificationStream::factory()->discord()->create();
    expect($notificationStream->formatted_type)->toBe('Discord Webhook');
});

it('returns correct formatted type for slack', function (): void {
    $notificationStream = NotificationStream::factory()->slack()->create();
    expect($notificationStream->formatted_type)->toBe('Slack Webhook');
});

it('returns correct formatted type for microsoft teams', function (): void {
    $notificationStream = NotificationStream::factory()->teams()->create();
    expect($notificationStream->formatted_type)->toBe('Teams Webhook');
});

it('returns correct type icon for email', function (): void {
    $notificationStream = NotificationStream::factory()->email()->create();
    expect($notificationStream->type_icon)->toBe('M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z');
});

it('returns correct type icon for discord', function (): void {
    $notificationStream = NotificationStream::factory()->discord()->create();
    expect($notificationStream->type_icon)->toBe('M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z');
});

it('returns correct type icon for slack', function (): void {
    $notificationStream = NotificationStream::factory()->slack()->create();
    expect($notificationStream->type_icon)->toBe('M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z');
});

it('returns correct type icon for teams', function (): void {
    $notificationStream = NotificationStream::factory()->teams()->create();
    expect($notificationStream->type_icon)->toBe('M 12.5 2 A 3 3 0 0 0 9.7089844 6.09375 C 9.4804148 6.0378189 9.2455412 6 9 6 L 4 6 C 2.346 6 1 7.346 1 9 L 1 14 C 1 15.654 2.346 17 4 17 L 9 17 C 10.654 17 12 15.654 12 14 L 12 9 C 12 8.6159715 11.921192 8.2518913 11.789062 7.9140625 A 3 3 0 0 0 12.5 8 A 3 3 0 0 0 12.5 2 z M 19 4 A 2 2 0 0 0 19 8 A 2 2 0 0 0 19 4 z M 4.5 9 L 8.5 9 C 8.776 9 9 9.224 9 9.5 C 9 9.776 8.776 10 8.5 10 L 7 10 L 7 14 C 7 14.276 6.776 14.5 6.5 14.5 C 6.224 14.5 6 14.276 6 14 L 6 10 L 4.5 10 C 4.224 10 4 9.776 4 9.5 C 4 9.224 4.224 9 4.5 9 z M 15 9 C 14.448 9 14 9.448 14 10 L 14 14 C 14 16.761 11.761 19 9 19 C 8.369 19 8.0339375 19.755703 8.4609375 20.220703 C 9.4649375 21.313703 10.903 22 12.5 22 C 15.24 22 17.529453 20.040312 17.939453 17.320312 C 17.979453 17.050312 18 16.78 18 16.5 L 18 11 C 18 9.9 17.1 9 16 9 L 15 9 z M 20.888672 9 C 20.322672 9 19.870625 9.46625 19.890625 10.03125 C 19.963625 12.09325 20 16.5 20 16.5 C 20 16.618 19.974547 16.859438 19.935547 17.148438 C 19.812547 18.048438 20.859594 18.653266 21.558594 18.072266 C 22.439594 17.340266 23 16.237 23 15 L 23 11 C 23 9.9 22.1 9 21 9 L 20.888672 9 z');
});

it('returns default type icon for unknown type', function (): void {
    $notificationStream = NotificationStream::factory()->create(['type' => 'unknown']);
    expect($notificationStream->type_icon)->toBe('M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z');
});

it('returns true when successful backup notifications are enabled', function (): void {
    $notificationStream = NotificationStream::factory()->successEnabled()->create();

    expect($notificationStream->hasSuccessfulBackupNotificationsEnabled())->toBeTrue();
});

it('returns false when successful backup notifications are disabled', function (): void {
    $notificationStream = NotificationStream::factory()->successDisabled()->create();

    expect($notificationStream->hasSuccessfulBackupNotificationsEnabled())->toBeFalse();
});

it('returns true when failed backup notifications are enabled', function (): void {
    $notificationStream = NotificationStream::factory()->failureEnabled()->create();

    expect($notificationStream->hasFailedBackupNotificationsEnabled())->toBeTrue();
});

it('returns false when failed backup notifications are disabled', function (): void {
    $notificationStream = NotificationStream::factory()->failureDisabled()->create();

    expect($notificationStream->hasFailedBackupNotificationsEnabled())->toBeFalse();
});
