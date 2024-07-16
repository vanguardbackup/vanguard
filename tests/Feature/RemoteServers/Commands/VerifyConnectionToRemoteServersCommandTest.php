<?php

declare(strict_types=1);

use App\Console\Commands\VerifyConnectionToRemoteServersCommand;
use App\Jobs\CheckRemoteServerConnectionJob;
use App\Models\RemoteServer;

it('dispatches batch job to check remote server connection', function (): void {
    Bus::fake();

    $remoteServerOne = RemoteServer::factory()->create();
    $remoteServerTwo = RemoteServer::factory()->create();
    $remoteServerThree = RemoteServer::factory()->create();

    $this->artisan(VerifyConnectionToRemoteServersCommand::class)
        ->expectsOutputToContain('Batch job dispatched to check remote server connection')
        ->assertExitCode(0);

    Bus::assertBatched(fn ($batch): bool => $batch->jobs->contains(fn ($job): bool => $job instanceof CheckRemoteServerConnectionJob
        && $job->remoteServerId === $remoteServerOne->id) && $batch->jobs->contains(fn ($job): bool => $job instanceof CheckRemoteServerConnectionJob
        && $job->remoteServerId === $remoteServerTwo->id) && $batch->jobs->contains(fn ($job): bool => $job instanceof CheckRemoteServerConnectionJob
        && $job->remoteServerId === $remoteServerThree->id));
});

it('exits if no remote servers found', function (): void {
    Bus::fake();
    $this->artisan(VerifyConnectionToRemoteServersCommand::class)
        ->expectsOutputToContain('No remote servers found. Exiting...')
        ->assertExitCode(0);

    Bus::assertNotDispatched(CheckRemoteServerConnectionJob::class);
});
