<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

enum QuizEligibilityResult: string
{
    case Eligible = 'eligible';
    case AccessDenied = 'access_denied';
    case LessonsIncomplete = 'lessons_incomplete';
    case QuizNotRequired = 'quiz_not_required';
    case InvalidQuiz = 'invalid_quiz';
    case AttemptsExhausted = 'attempts_exhausted';
    case AlreadyPassed = 'already_passed';

    public function isEligible(): bool
    {
        return self::Eligible === $this;
    }
}
