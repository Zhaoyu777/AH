<?php

namespace CustomBundle\Biz\Lesson\Service\Impl;

use Biz\BaseService;
use CustomBundle\Biz\Lesson\Service\TeachingAimWarningService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;

class TeachingAimWarningServiceImpl extends BaseService implements TeachingAimWarningService
{
    public function batchCreate($rows)
    {
        if (empty($rows)) {
            return ;
        }

        foreach ($rows as $row) {
            $this->checkRow($row);
        }

        $this->getCourseTeachingAimWarningDao()->batchCreate($rows);
    }

    protected function checkRow($row)
    {
        if (!ArrayToolkit::requireds($row, array('courseId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $row = ArrayToolkit::parts($row, array(
            'courseId',
            'times'
        ));

        return $row;
    }

    public function addWarningTimeByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return;
        }

        return $this->getCourseTeachingAimWarningDao()->waveByCourseIds($courseIds, array('times' => 1));
    }

    public function deleteByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return;
        }

        return $this->getCourseTeachingAimWarningDao()->deleteByCourseIds($courseIds);
    }

    public function findAllWarningCourses()
    {
        return $this->getCourseTeachingAimWarningDao()->findAllWarningCourses();
    }

    public function findTeachingAimWarningCoursesByCourseIds($courseIds)
    {
        return $this->getCourseTeachingAimWarningDao()->findByCourseIds($courseIds);
    }

    protected function getCourseTeachingAimWarningDao()
    {
        return $this->createDao('CustomBundle:Lesson:CourseTeachingAimWarningDao');
    }
}
