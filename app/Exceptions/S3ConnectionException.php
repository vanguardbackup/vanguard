<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when encountering S3 connection issues.
 * Used to handle errors related to establishing or maintaining S3 connections.
 */
class S3ConnectionException extends Exception
{
    //
}
