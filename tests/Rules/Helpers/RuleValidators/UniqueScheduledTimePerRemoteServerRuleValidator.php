<?php

declare(strict_types=1);

namespace Tests\Rules\Helpers\RuleValidators;

use App\Rules\UniqueScheduledTimePerRemoteServer;

class UniqueScheduledTimePerRemoteServerRuleValidator
{
    public static function validate(UniqueScheduledTimePerRemoteServer $uniqueScheduledTimePerRemoteServer, string $value): bool
    {
        $fails = false;
        $fail = function () use (&$fails): void {
            $fails = true;
        };

        $uniqueScheduledTimePerRemoteServer->validate('time_to_run_at', $value, $fail);

        return ! $fails;
    }
}
