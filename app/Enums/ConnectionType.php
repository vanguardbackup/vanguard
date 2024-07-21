<?php

declare(strict_types=1);

namespace App\Enums;

enum ConnectionType: string
{
    case SSH = 'ssh';
    case SFTP = 'sftp';
}
