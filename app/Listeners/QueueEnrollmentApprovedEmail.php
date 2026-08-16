<?php

namespace App\Listeners;

use App\Events\EnrollmentApproved;
use App\Services\NotificationService;

class QueueEnrollmentApprovedEmail
{
    public function __construct(private readonly NotificationService $avisos) {}

    public function handle(EnrollmentApproved $event): void
    {
        $this->avisos->enrollmentApproved($event->enrollment);
    }
}
