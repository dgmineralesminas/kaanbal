<?php

declare(strict_types=1);

namespace Kaanbal\Quiz;

use Kaanbal\Access\Application\CourseAccessService;
use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Courses\Application\CurriculumService;
use Kaanbal\Courses\Infrastructure\CurriculumRepository;
use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Progress\Infrastructure\LessonProgressRepository;
use Kaanbal\Quiz\Application\CourseCompletionService;
use Kaanbal\Quiz\Application\QuizEligibilityService;
use Kaanbal\Quiz\Application\QuizValidityService;
use Kaanbal\Quiz\Application\QuizScoreCalculator;
use Kaanbal\Quiz\Application\QuizSubmissionService;
use Kaanbal\Quiz\Infrastructure\AnswerRepository;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;
use Kaanbal\Quiz\Presentation\Admin\QuizMetaBoxes;
use Kaanbal\Quiz\Presentation\Frontend\QuizSubmissionAction;

final class QuizModule implements BootableService
{
    public function register(): void
    {
        $curriculum = new CurriculumService(new CurriculumRepository());
        $progress = new CourseProgressService($curriculum, new LessonProgressRepository());
        $enrollments = new EnrollmentRepository();
        $quizzes = new QuizRepository();
        $questions = new QuestionRepository();
        $answers = new AnswerRepository();
        $attempts = new QuizAttemptRepository();
        $eligibility = new QuizEligibilityService(new CourseAccessService($enrollments), $progress, $quizzes, new QuizValidityService($questions), $attempts);
        $completion = new CourseCompletionService($progress, $quizzes, $attempts, $enrollments);
        $submission = new QuizSubmissionService($eligibility, $quizzes, $questions, $answers, $attempts, new QuizScoreCalculator(), $completion);
        $meta_boxes = new QuizMetaBoxes();
        $submit = new QuizSubmissionAction($submission);

        add_action('add_meta_boxes', array($meta_boxes, 'register'));
        add_action('save_post_kaanbal_course', array($meta_boxes, 'saveCourse'));
        add_action('save_post_kaanbal_quiz', array($meta_boxes, 'saveQuiz'));
        add_action('admin_post_kaanbal_submit_final_quiz', array($submit, 'handle'));
        add_action('admin_post_nopriv_kaanbal_submit_final_quiz', array($submit, 'handle'));
    }
}
