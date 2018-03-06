<?php

namespace CustomBundle\Biz\Statistics\Statistics;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Statistics extends AbstractStatistics
{
    private $courseStatistics;
    private $teacherStatistics;

    private $lessonIds;
    private $userIds;

    public function __construct($options = array())
    {
        $this->courseStatistics = new CourseStatistics();
        $this->teacherStatistics = new TeacherCourseStatistics();

        if (!empty($options['lessonIds'])) {
            $this->lessonIds = $options['lessonIds'];
        } 

        if (!empty($options['userIds'])) {
            $this->userIds = $options['userIds'];
        }
    }

    public function statistics()
    {
        if (count($this->lessonIds)) {
            $params = $this->courseStatistics($this->lessonIds);
        }

        if (count($this->userIds)) {
            $params = $this->teacherStatistics($this->userIds);
        }
    }

    public function courseStatistics($lessonIds)
    {
        $lessons = $this->getCourseLessonService()->findCourseLessonsByIds($lessonIds);
        foreach ($lessons as $lesson) {
            $this->courseStatistics->statistics($lesson);
        }
    }

    public function teacherStatistics($userIds)
    {
        foreach ($userIds as $userId) {
            $this->teacherStatistics->statistics($userId);
        }
    }

    protected function getBiz()
    {
        return $this->container->get('biz');
    }

    protected function getLogService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:System:LogService');
    }

    protected function getTaskService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Task:TaskService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:User:UserService');
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }
}
