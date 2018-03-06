<?php

namespace AppBundle\Extensions\DataTag;

use Biz\Announcement\Service\AnnouncementService;

/**
 * @todo
 */
class CourseAnnouncementsDataTag extends BaseDataTag implements DataTag
{
    /**
     * 获取课程公告列表.
     *
     * 可传入的参数：
     *   courseSetId  必需
     *   courseId     可选
     *   count        必需 取值不超过10
     *
     * @param array $arguments 参数
     *
     * @return array 公告列表
     */
    public function getData(array $arguments)
    {
        $this->checkCount($arguments);

        $conditions = array(
            'targetType' => 'course',
            'endTime' => time(),
        );

        if (!empty($arguments['courseId'])) {
            $conditions['targetId'] = $arguments['courseId'];
        }

        $announcements = $this->getAnnouncementService()->searchAnnouncements($conditions, array('createdTime' => 'DESC'), 0, $arguments['count']);

        return $announcements;
    }

    /**
     * @return AnnouncementService
     */
    protected function getAnnouncementService()
    {
        return $this->getServiceKernel()->createService('Announcement:AnnouncementService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->getBiz()->service('Course:CourseService');
    }

    protected function checkCount(array $arguments)
    {
        if (empty($arguments['count'])) {
            throw new \InvalidArgumentException('count argument missing');
        }
        if ($arguments['count'] > 100) {
            throw new \InvalidArgumentException('invalid arguments');
        }

        if (empty($arguments['courseId'])) {
            throw new \InvalidArgumentException('invalid arguments');
        }
    }
}
