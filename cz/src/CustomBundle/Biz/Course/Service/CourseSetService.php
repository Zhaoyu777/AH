<?php

namespace CustomBundle\Biz\Course\Service;

use Biz\Course\Service\CourseSetService as BaseCourseSetService;

interface CourseSetService extends BaseCourseSetService
{
    public function createInstantCourseSet($courseSet);
}
