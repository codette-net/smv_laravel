<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';

    case Processing = 'processing';

    case Completed = 'completed';

    case Incomplete = 'incomplete';

    case Skipped = 'skipped';

    case Failed = 'failed';
}
