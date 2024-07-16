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
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $remoteServerId)
    {
        //
    }

    public function handle(): void
    {
        $checkRemoteServerConnection = new CheckRemoteServerConnection;
        $checkRemoteServerConnection->byRemoteServerId($this->remoteServerId);
    }
}
