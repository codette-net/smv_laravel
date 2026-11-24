<?php

namespace App\Payments;

enum FakePaymentOutcome: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
