<?php

namespace CustomBundle\Biz\Statistics\Service;

interface StudentCourseStatisticsService
{
    public function createStudentsCourseStatistics($fields);

    public function updateStudentsCourseStatistics($id, $fields);

    public function getStudentsCourseStatisticsByUserIdAndCourseId($userId, $courseId);

    public function getPercentageByUserIdAndCourseId($userId, $courseId);

    public function countStudentsCourseStatistics($conditions);

    public function searchStudentsCourseStatistics($conditions, $order, $start, $limit);
}
