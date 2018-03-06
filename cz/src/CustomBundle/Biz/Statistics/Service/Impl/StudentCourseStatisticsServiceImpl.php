<?php

namespace CustomBundle\Biz\Statistics\Service\Impl;

use CustomBundle\Biz\Statistics\Service\StudentCourseStatisticsService;
use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;

class StudentCourseStatisticsServiceImpl extends BaseService implements StudentCourseStatisticsService
{
    public function createStudentsCourseStatistics($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('userId', 'courseId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::parts($fields, array(
            'userId',
            'courseId',
            'studentAttendence',
            'taskInCompletionRate',
            'taskOutCompletionRate',
            'averageGrades',
            'totalScore',
        ));

        return $this->getStudentCourseStatisticsDao()->create($fields);
    }

    public function updateStudentsCourseStatistics($id, $fields)
    {
        return $this->getStudentCourseStatisticsDao()->update($id, $fields);
    }

    public function getStudentsCourseStatisticsByUserIdAndCourseId($userId, $courseId)
    {
        return $this->getStudentCourseStatisticsDao()->getByUserIdAndCourseId($userId, $courseId);
    }

    public function getStudentsMultiAnalysisByCourseId($courseId)
    {
        if (empty($courseId)) {
            return array();
        }

        //$this->getCourseService()->tryManageCourse($courseId);

        return $this->getStudentCourseStatisticsDao()->getStudentsMultiAnalysisByCourseId($courseId);
    }

    public function getPercentageByUserIdAndCourseId($userId, $courseId)
    {
        $user = $this->getUserService()->getUser($userId);
        $members = $this->getCourseMemberService()->findCourseStudents($courseId, 0, PHP_INT_MAX);
        $userIds = ArrayToolkit::column($members, 'userId');
        $statistics = $this->getStudentsCourseStatisticsByUserIdAndCourseId($user['id'], $courseId);

        if (empty($statistics)) {
            return;
        }

        $percentage = array(
            'studentAttendence' => 0,
            'taskInCompletionRate' => 0,
            'taskOutCompletionRate' => 0,
        );

        foreach ($percentage as $key => &$column) {
            if (!isset($statistics[$key])) {
                continue;
            }
            $total = $this->getStudentCourseStatisticsDao()->count(array(
                'courseId' => $courseId,
                'userIds' => $userIds,
                'gt'.$key => 0,
            ));

            $gtCount = $this->getStudentCourseStatisticsDao()->count(array(
                'gt'.$key => $statistics[$key],
                'courseId' => $courseId,
                'userIds' => $userIds,
            ));
            $count = $this->getStudentCourseStatisticsDao()->count(array(
                $key => $statistics[$key],
                'courseId' => $courseId,
                'userIds' => $userIds,
            ));

            $ltCount = $total - $gtCount;
            $gtCount = $gtCount - $count;

            $column = array();
            if ($gtCount > $ltCount) {
                $column['gtRate'] = $this->countRate($gtCount, $total);
            } else {
                $column['ltRate'] = $this->countRate($ltCount, $total);
            }
        }

        return $percentage;
    }

    protected function countRate($count, $total)
    {
        if ($total - 1 <= 0) {
            return ;
        }

        $rate = round($count / ($total - 1), 3);

        if ($rate > 1) {
            $rate = 1;
        } elseif ($rate < 0) {
            $rate = 0;
        }

        return $rate * 100;
    }

    public function countStudentsCourseStatistics($conditions)
    {
        $this->getCourseService()->tryManageCourse($conditions['courseId']);

        return $this->getStudentCourseStatisticsDao()->count($conditions);
    }

    public function searchStudentsCourseStatistics($conditions, $order, $start, $limit)
    {
        $this->getCourseService()->tryManageCourse($conditions['courseId']);

        return $this->getStudentCourseStatisticsDao()->search($conditions, $order, $start, $limit);
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getUserService()
    {
        return $this->createService('user:UserService');
    }

    protected function getStudentCourseStatisticsDao()
    {
        return $this->createDao('CustomBundle:Statistics:StudentCourseStatisticsDao');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
}
