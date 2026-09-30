<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Application;

interface LessonProgressStore
{
    /** Returns true only when a new completion was persisted. */
    public function complete(int $user_id, int $lesson_id): bool;

    /** @param list<int> $lesson_ids
     * @return list<int>
     */
    public function findCompletedLessonIds(int $user_id, array $lesson_ids): array;

    /**
     * @param list<int> $user_ids
     * @param list<int> $lesson_ids
     * @return array<int, list<int>> Completed lesson IDs keyed by user ID.
     */
    public function findCompletedLessonIdsForUsers(array $user_ids, array $lesson_ids): array;
}
