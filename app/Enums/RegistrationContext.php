<?php

namespace App\Enums;

enum RegistrationContext: string
{
    case JobSeeker = 'job_seeker';
    case Employer = 'employer';
}
