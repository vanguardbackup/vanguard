<?php

use App\Mail\BackupTaskFailed;
use App\Models\BackupTask;
use App\Models\User;

test('the contents are correct', function () {
    $user = User::factory()->create();
    $taskLabel = BackupTask::factory()->create()->label;
    $errorMessage = 'error-message';

    $mail = new BackupTaskFailed($user, $taskLabel, $errorMessage);

    $mail->assertHasSubject(__('Backup task failed'));
    $mail->assertSeeInHtml($taskLabel);
    $mail->assertSeeInHtml($errorMessage);
    $mail->assertSeeInHtml($user->first_name);
    $mail->assertSeeInHtml(__('Backup task failed'));
});
