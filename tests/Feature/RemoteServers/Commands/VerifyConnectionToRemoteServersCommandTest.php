<?php

use App\Console\Commands\VerifyConnectionToRemoteServersCommand;
use App\Jobs\CheckRemoteServerConnectionJob;
use App\Models\RemoteServer;

it('dispatches batch job to check remote server connection', function () {
    Bus::fake();

    $remoteServerOne = RemoteServer::factory()->create();
    $remoteServerTwo = RemoteServer::factory()->create();
    $remoteServerThree = RemoteServer::factory()->create();

    $this->artisan(VerifyConnectionToRemoteServersCommand::class)
        ->expectsOutputToContain('Batch job dispatched to check remote server connection')
        ->assertExitCode(0);

    Bus::assertBatched(function ($batch) use ($remoteServerOne, $remoteServerTwo, $remoteServerThree) {
        return $batch->jobs->contains(function ($job) use ($remoteServerOne) {
            return $job instanceof CheckRemoteServerConnectionJob
                && $job->remoteServerId === $remoteServerOne->id;
        }) && $batch->jobs->contains(function ($job) use ($remoteServerTwo) {
            return $job instanceof CheckRemoteServerConnectionJob
                && $job->remoteServerId === $remoteServerTwo->id;
        }) && $batch->jobs->contains(function ($job) use ($remoteServerThree) {
            return $job instanceof CheckRemoteServerConnectionJob
                && $job->remoteServerId === $remoteServerThree->id;
        });
    });
});

it('exits if no remote servers found', function () {
    Bus::fake();
    $this->artisan(VerifyConnectionToRemoteServersCommand::class)
        ->expectsOutputToContain('No remote servers found. Exiting...')
        ->assertExitCode(0);

    Bus::assertNotDispatched(CheckRemoteServerConnectionJob::class);
});
