<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\CourseWarningDao;
use CustomBundle\Biz\Course\Service\CourseWarningService;

class CourseWarningServiceImpl extends BaseService implements CourseWarningService
{
    public function create($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('courseId', 'type'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $warn = ArrayToolkit::parts($fields, array(
            'courseId',
            'type',
        ));

        $warn = $this->getCourseWarningDao()->create($fields);

        return $warn;
    }

    public function recordWarning($type, $courseIds)
    {
        $warnRecors = $this->search(array('type' => $type), array(), 0, PHP_INT_MAX);

        $recorsCourses = ArrayToolkit::index($warnRecors, 'courseId');

        foreach ($courseIds as $courseId) {
            if (!empty($recorsCourses[$courseId])) {
                $id = $recorsCourses[$courseId]['id'];
                $fields = array(
                    'continuous' => $recorsCourses[$courseId]['continuous'] + 1
                );
                $this->getCourseWarningDao()->update($id, $fields);
                unset($recorsCourses[$courseId]);
            } else {
                $fields = array(
                    'courseId' => $courseId,
                    'continuous' => 1,
                    'type' => $type,
                );
                $this->create($fields);
            }
        }

        foreach ($recorsCourses as $recorsCourse) {
            $this->deleteCourseWarning($recorsCourse['id']);
        }
    }

    public function deleteCourseWarning($warningId)
    {
        return $this->getCourseWarningDao()->delete($warningId);
    }

    public function findCourseByTypeAndContinuous($type ,$continuous)
    {
        $conditions = array(
            'gtContinuous' => $continuous,
            'type' => $type,
        );

        $courseWarnings = $this->search(
            $conditions,
            array(),
            0,
            PHP_INT_MAX
        );

        return $courseWarnings;
    }

    public function search($conditions, $orderBy, $start, $limit)
    {
        return $this->getCourseWarningDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function getCourseWarningDao()
    {
        return $this->createDao('CustomBundle:Course:CourseWarningDao');
    }
}