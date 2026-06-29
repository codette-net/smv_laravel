<?php

namespace App\Enums;

enum VacancySource: string {
    case Manual = 'manual';
    case Import = 'import';
    case Api = 'api';
}

