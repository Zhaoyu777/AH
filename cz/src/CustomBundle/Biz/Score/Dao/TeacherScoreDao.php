<?php

namespace CustomBundle\Biz\Score\Dao;

interface TeacherScoreDao
{
    public function findByUserId($userId);

    public function findByTermAndUserId($term, $userId);

    public function findUserSumScoresByCourseId($courseId);

    public function sumScoreByTermAndUserId($term, $userId);

    public function getByLessonAndUserIdAndSource($lessonId, $userId, $source);
}
