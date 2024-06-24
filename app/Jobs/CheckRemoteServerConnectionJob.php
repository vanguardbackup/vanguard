<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RemoteServer\CheckRemoteServerConnection;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckRemoteServerConnectionJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $remoteServerId)
    {
        //
    }

    public function handle(): void
    {
        $checkConnection = new CheckRemoteServerConnection;
        $checkConnection->byRemoteServerId($this->remoteServerId);
    }
}
