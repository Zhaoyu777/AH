<?php

namespace AppBundle\Extensions\DataTag;

class PublishedCourseByCourseSetDataTag extends CourseBaseDataTag implements DataTag
{
    /**
     * 获取第一个发布的教学计划.
     *
     * 可传入的参数：
     *   courseSetId 必需 课程ID
     *
     * @param array $arguments 参数
     *
     * @throws \InvalidArgumentException
     *
     * @return array 计划
     */
    public function getData(array $arguments)
    {
        if (empty($arguments['courseSetId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('courseSetId参数缺失'));
        }

        return $this->getCourseService()->getFirstPublishedCourseByCourseSetId($arguments['courseSetId']);
    }
}
