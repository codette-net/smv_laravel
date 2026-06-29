<?php

namespace App\Enums;

enum ImportLogLevel: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
