<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

enum QuizSubmissionResult: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case Rejected = 'rejected';
}
