<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Script;
use App\Services\Backup\Contracts\SFTPInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ScriptExecutionService
{
    /**
     * Execute a script safely on the remote server.
     *
     * @param  SFTPInterface  $sftp  The SFTP connection
     * @param  Script  $script  The script to execute
     * @return string The script output
     *
     * @throws Exception If script execution fails
     */
    public function executeScript(SFTPInterface $sftp, Script $script): string
    {
        $tempFilename = '/tmp/script_' . Str::uuid()->toString();

        try {
            $sftp->put($tempFilename, $script->getAttribute('script'));

            $sftp->exec('chmod +x ' . $tempFilename);

            $output = $sftp->exec($tempFilename . ' 2>&1');

            $sftp->exec('rm -f ' . $tempFilename);

            return (string) $output;
        } catch (Exception $e) {
            try {
                $sftp->exec('rm -f ' . $tempFilename);
            } catch (Exception $cleanupException) {
                Log::error('Failed to clean up script file: ' . $cleanupException->getMessage());
            }

            throw new RuntimeException('Script execution failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
