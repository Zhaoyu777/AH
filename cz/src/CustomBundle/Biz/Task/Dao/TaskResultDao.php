<?php

namespace CustomBundle\Biz\Task\Dao;

interface TaskResultDao
{
    public function findCourseIdsBetweenFromTimeAndToTime($fromTime, $toTime);

    public function countStudentsTasksByCourseIdAndStatus($courseId, $status);

    public function getByCourseIdAndTaskId($courseId, $taskId);

    public function findByCourseIdAndUserIds($courseId, $userIds);

    public function findResultsByCourseIdAndUserIds($courseId, $userIds);

    public function getLastResultByCourseIdAndUserId($courseId, $userId, $createdTime);
}
