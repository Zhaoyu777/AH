<?php

namespace CustomBundle\Biz\Statistics\Statistics;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TeacherCourseStatistics
{
    private $courseIds;

    private $userId;

    private $termCode;

    protected function init($userId)
    {
        $termCode = $this->getCourseService()->getCurrentTerm();
        $this->termCode = $termCode;

        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($userId);
        $courseIds = ArrayToolkit::column($members, 'courseId');
        $courses = $this->getCourseService()->findSchoolCoursesByIdsAndTermCode($courseIds, $termCode['shortCode']);
        $this->courseIds = ArrayToolkit::column($courses, 'id');

        $this->userId = $userId;
    }

    public function statistics($userId)
    {
        $this->init($userId);

        $statistics = $this->getTeacherCourseStatisticsService()->getStatisticsByUserIdAndTermCode($userId, $this->termCode['shortCode']);

        $fields = array(
            'userId' => (int)$userId,
        );
        //循环获取统计项
        foreach ($this->getUpdateColumns() as $column) {
            $func = 'get'.ucfirst($column);
            $rate = $this->$func();
            $fields[$column] = $rate;
        }

        if (empty($this->courseIds)) {
            $nullFields = array(
                'studentAttendRate' => null,
                'taskOuterCompletionRate' => null,
                'taskInCompletionRate' => null,
                'homeworkNum' => 0,
                'analysisNum' => 0,
                'resourcesIncreaseNum' => 0,
                'teachingAimsFinishedRate' => null
            );

            $fields = array_merge($fields, $nullFields);
        }

        if (empty($statistics)) {
            $fields['termCode'] = $this->termCode['shortCode'];
            $fields['loginDays'] = 1;
            $statistics = $this->getTeacherCourseStatisticsService()->createStatistics($fields);
        } else {
            $fields['loginDays'] = $statistics['loginDays'] + 1;
            $statistics = $this->getTeacherCourseStatisticsService()->updateStatistics($statistics['id'], $fields);
        }

        return $statistics;
    }

    protected function getCourseLessonRate()
    {
        $lessonCourses = $this->getTaskService()->findStatisticsTaskCountByUserId($this->userId);
        $count = ArrayToolkit::column($lessonCourses, 'courseId');

        $courseIds = $this->courseIds;
        if (empty($courseIds)) {
            return null;
        }

        $rate = count(array_intersect($count, $courseIds)) / count($courseIds);

        return round($rate, 3);
    }

    protected function getLessonRate()
    {
        if (empty($this->courseIds)) {
            return null;
        }

        $total = $this->getCourseLessonService()->countLessonByCourseIds($this->courseIds);

        if (!$total) {
            return 0;
        }
        $count = $this->getCourseLessonService()->countStatisticsLessonTaskByCourseIds($this->courseIds);

        return round($count / $total, 3);
    }

    protected function getStudentAttendRate()
    {
        $rate = $this->getCourseStatisticsService()->getStudentAttendRateByCourseIdsAndColumns($this->courseIds, 'studentAttendRate');

        return $rate === null ? null : round($rate, 3);
    }

    protected function getTaskOuterCompletionRate()
    {
        $after = $this->getCourseStatisticsService()->getStudentAttendRateByCourseIdsAndColumns($this->courseIds, 'taskAfterCompletionRate');
        $before = $this->getCourseStatisticsService()->getStudentAttendRateByCourseIdsAndColumns($this->courseIds, 'taskBeforeCompletionRate');

        if ($after === null && $before === null) {
            return null;
        }

        return round(($after + $before) / 2, 3);
    }

    protected function getTaskInCompletionRate()
    {
        $rate = $this->getCourseStatisticsService()->getStudentAttendRateByCourseIdsAndColumns($this->courseIds, 'taskInCompletionRate');

        return $rate === null ? null : round($rate, 3);
    }

    protected function getHomeworkNum()
    {
        $conditions = array(
            'courseIds' => $this->courseIds,
            'types' => array('homework', 'practiceWork'),
        );

        return $this->getTaskService()->countTasks($conditions);
    }

    protected function getAnalysisNum()
    {
         return $this->getCourseLessonService()->countLessonByCourseIdsAndStatus($this->courseIds, 'teached');
    }

    protected function getResourcesNum()
    {
        return $this->getFileService()->searchFileCount(array(
            'createdUserId' => $this->userId
        ));
    }

    protected function getResourcesIncreaseNum()
    {
        if (empty($this->termCode)) {
            return 0;
        }
        
        $shortCode = $this->termCode['shortCode'];

        $dataterm = $pieces = explode("-", $shortCode);
        if ($dataterm[2] == 2) {
            $dataterm[2] = 1;
        } elseif ($dataterm[2] == 1) {
            $dataterm[0] = $dataterm[1] - 1;
            $dataterm[1] = $dataterm[1];
            $dataterm[2] = 2;
        }

        $lastTerm = implode("-", $dataterm);

        $lastResources = $this->getFileService()->searchFileCount(array(
            'createdUserId' => $this->userId,
            'termCode' => $lastTerm,
        ));

        $currentResources = $this->getFileService()->searchFileCount(array(
            'createdUserId' => $this->userId,
            'termCode' => $shortCode,
        ));

        return $currentResources - $lastResources;
    }

    protected function getResourcesQuoteNum()
    {
        $total = $this->getMaterialService()->countStatisticsByUserId($this->userId);

        return $total;
    }

    protected function getTeachingAimsFinishedRate()
    {
        $rates = $this->getTeachingAimActivityService()->calcTeacherFinishedRate($this->userId);

        $result = array();
        foreach ($rates as $rate) {
            if (!in_array($rate['courseId'], $this->courseIds)) {
                continue;
            }
            $result[] = $rate['rate'];
        }

        if (!count($result)) {
            return 0;
        }

        return array_sum($result) / count($result);
    }

    protected function getTeacherCourseStatisticsService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Statistics:TeacherCourseStatisticsService');
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getLogService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:System:LogService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseMemberService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:MemberService');
    }

    protected function getTaskService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseStatisticsService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Statistics:CourseStatisticsService');
    }

    protected function getFileService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:File:UploadFileService');
    }

    protected function getMaterialService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:MaterialService');
    }

    protected function getTeachingAimActivityService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Lesson:TeachingAimActivityService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

    private function getUpdateColumns()
    {
        $columns = array(
            'courseLessonRate',
            'lessonRate',
            'resourcesNum',
            'resourcesQuoteNum'
        );

        if (!empty($this->courseIds)) {
            $courseColumns = array(
                'studentAttendRate',
                'taskOuterCompletionRate',
                'taskInCompletionRate',
                'homeworkNum',
                'analysisNum',
                'resourcesIncreaseNum',
                'teachingAimsFinishedRate',
            );
            $columns = array_merge($courseColumns, $columns);
        }

        return $columns;
    }
}
