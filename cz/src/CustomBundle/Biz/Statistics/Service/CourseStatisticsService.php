<?php

namespace CustomBundle\Biz\Statistics\Service;

interface CourseStatisticsService
{
    public function createStatistics($fields);

    public function updateStatistics($id, $fields);

    public function findStatisticsByCourseId($courseId);

    public function findTeachingAimWarningCoursesByValue($value);

    public function getStatisticsByLessonId($lessonId);

    public function searchStatistics($conditions, $orderBy, $start, $limit);
}
