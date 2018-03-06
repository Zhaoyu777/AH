<?php

namespace CustomBundle\Biz\Statistics\Service;

interface TeacherCourseStatisticsService
{
    public function createStatistics($fields);

    public function updateStatistics($id, $fields);

    public function getStatisticsByUserId($userId);

    public function searchStatistics($conditions, $orderBy, $start, $limit);
}
