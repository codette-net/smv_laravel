<?php

namespace App\Enums;

enum PaymentStatus: string
{
    /** Historical open state; new attempts use Pending. */
    case Open = 'open';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';
}
