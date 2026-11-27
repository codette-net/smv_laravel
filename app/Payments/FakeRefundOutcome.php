<?php

namespace App\Payments;

enum FakeRefundOutcome: string
{
    case Success = 'success';
    case Pending = 'pending';
    case Failed = 'failed';
    case Indeterminate = 'indeterminate';
}
