<?php

declare(strict_types=1);

namespace Kaanbal\Access\Application;

enum CourseAccessResult: string
{
    case Granted = 'granted';
    case NotAuthenticated = 'not_authenticated';
    case NotEnrolled = 'not_enrolled';
    case Revoked = 'revoked';

    public function isGranted(): bool
    {
        return self::Granted === $this;
    }
}
