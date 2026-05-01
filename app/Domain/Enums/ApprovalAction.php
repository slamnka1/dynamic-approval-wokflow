<?php

namespace App\Domain\Enums;

enum ApprovalAction: string
{
    case Approve = 'approve';
    case Reject = 'reject';
}
