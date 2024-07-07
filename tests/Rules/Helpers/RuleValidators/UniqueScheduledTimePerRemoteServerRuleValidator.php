<?php

namespace Tests\Rules\Helpers\RuleValidators;

use App\Rules\UniqueScheduledTimePerRemoteServer;

class UniqueScheduledTimePerRemoteServerRuleValidator
{
    public static function validate(UniqueScheduledTimePerRemoteServer $rule, string $value): bool
    {
        $fails = false;
        $fail = function () use (&$fails): void {
            $fails = true;
        };

        $rule->validate('time_to_run_at', $value, $fail);

        return ! $fails;
    }
}
