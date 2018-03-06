<?php

namespace CustomBundle\Biz\Statistics\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Statistics\Dao\CourseStatisticsDao;
use CustomBundle\Biz\Statistics\Service\CourseStatisticsService;

class CourseStatisticsServiceImpl extends BaseService implements CourseStatisticsService
{
    public function tryStatistics($fields)
    {
    }

    public function createStatistics($fields)
    {
        $this->tryStatistics($fields);

        $user = $this->getCurrentUser();

        $created = $this->getStatisticsDao()->create($fields);

        return $created;
    }

    public function updateStatistics($id, $fields)
    {
        $this->tryStatistics($fields);

        return $this->getStatisticsDao()->update($id, $fields);
    }

    public function findStatisticsByCourseId($courseId)
    {
        return $this->getStatisticsDao()->findByCourseId($courseId);
    }

    public function findTeachingAimWarningCoursesByValue($value)
    {
        return $this->getStatisticsDao()->findTeachingAimWarningCoursesByValue($value);
    }

    public function getStatisticsByLessonId($lessonId)
    {
        return $this->getStatisticsDao()->getByLessonId($lessonId);
    }

    public function searchStatistics($conditions, $orderBy, $start, $limit)
    {
        return $this->getGroupDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function getStudentAttendRateByCourseIdsAndColumns($courseIds, $columns)
    {
        return $this->getStatisticsDao()->countAvgByCourseIdsAndColumns($courseIds, $columns);
    }

    public function findCoursesInCompleByTermCodeAndWarnValue($termCode, $warnValue)
    {
        return $this->getStatisticsDao()->findByTermCodeAndWarnValue($termCode, $warnValue);
    }

    protected function getStatisticsDao()
    {
        return $this->createDao('CustomBundle:Statistics:CourseStatisticsDao');
    }
}
