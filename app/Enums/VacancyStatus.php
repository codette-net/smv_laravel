<?php

namespace App\Enums;

enum VacancyStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'published';
    case Expired = 'expired';
    case Archived = 'archived';
}
