<?php

namespace Biz\Activity\Service;

use Biz\Activity\Config\Activity;

interface ActivityService
{
    public function getActivity($id, $fetchMedia = false);

    public function getActivityByCopyIdAndCourseSetId($copyId, $courseSetId);

    public function findActivities($ids, $fetchMedia = false);

    public function findActivitiesByCourseIdAndType($courseId, $type, $fetchMedia = false);

    public function findActivitiesByCourseSetIdAndType($courseSetId, $type, $fetchMedia = false);

    /**
     * 创建之前检查完整性
     *
     * @param $activityType
     * @param $params
     *
     * @return mixed
     */
    public function preCreateCheck($activityType, $fields);

    /**
     * 更新之前检查完整性
     *
     * @param $activityType
     * @param $params
     *
     * @return mixed
     */
    public function preUpdateCheck($activityId, $fields);

    public function createActivity($activity);

    public function updateActivity($id, $fields);

    public function deleteActivity($id);

    public function search($conditions, $orderBy, $start, $limit);

    public function count($conditions);

    /**
     * @param string $type 活动类型
     *
     * @return Activity
     */
    public function getActivityConfig($type);

    public function trigger($activityId, $name, $data = array());

    public function isFinished($activityId);

    public function findActivitySupportVideoTryLook($courseIds);
}
