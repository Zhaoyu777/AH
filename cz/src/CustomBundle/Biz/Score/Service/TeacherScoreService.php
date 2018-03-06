<?php

namespace CustomBundle\Biz\Score\Service;

interface TeacherScoreService
{
    public function createTeacherScore($teacherScore);

    public function isGainScoreByLessonIdAndSource($lessonId, $source);

    public function findTeacherScoresByTermAndUserId($term, $userId);

    public function getSumScoreByTermAndUserId($term, $userId);

    public function findTeacherScoresByLessonIdAndUserId($lessonId, $userId);
}
