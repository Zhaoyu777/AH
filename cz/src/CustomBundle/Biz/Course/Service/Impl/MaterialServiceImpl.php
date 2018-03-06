<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\MaterialService;
use Biz\Course\Service\Impl\MaterialServiceImpl as BaseMaterialServiceImpl;

class MaterialServiceImpl extends BaseMaterialServiceImpl implements MaterialService
{
    public function findMaterialsByLessonId($lessonId)
    {
        return $this->getMaterialDao()->findByLessonId($lessonId);
    }

    public function countStatisticsByCourseIdsAndUserId($courseIds, $userId)
    {
        return $this->getMaterialDao()->countStatisticsByCourseIdsAndUserId($courseIds, $userId);
    }

    public function countStatisticsByUserId($userId)
    {
        return $this->getMaterialDao()->countStatisticsByUserId($userId);
    }

    public function countCourseSetAllFilesByCourseSetId($courseSetId)
    {
        return $this->getMaterialDao()->countCourseSetAllFilesByCourseSetId($courseSetId);
    }

    public function countMaterialByUserId($userId)
    {
        return $this->getMaterialDao()->countMaterialByUserId($userId);
    }

    protected function getMaterialDao()
    {
        return $this->createDao('CustomBundle:Course:CourseMaterialDao');
    }
}
