<?php

namespace CustomBundle\Biz\Lesson\Dao;

interface EvaluationDao
{
    public function findByCourseIdAndUserId($courseId, $userId);

    public function getByLessonIdAndUserId($lessonId, $userId);

    public function findByCourseId($courseId);

    public function findCourseIdsBetweenFromTimeAndToTime($fromTime, $toTime);
}
