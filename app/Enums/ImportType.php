<?php

namespace App\Enums;

enum ImportType: string
{
    case csv = 'csv';
    case xml = 'xml';
    case json = 'json';
    case api = 'api';
    case manual = 'manual';
}
