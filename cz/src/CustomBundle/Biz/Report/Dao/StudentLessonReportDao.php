<?php

namespace CustomBundle\Biz\Report\Dao;

interface StudentLessonReportDao
{
    public function findBylessonId($lessonId);

    public function getBylessonIdAndUserId($lessonId, $userId);

    public function findBycourseId($courseId);

    public function findBycourseIdAndUserId($courseId, $userId);
}
