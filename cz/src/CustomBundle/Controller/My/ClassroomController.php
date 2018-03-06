<?php

namespace CustomBundle\Controller\My;

use AppBundle\Controller\BaseController;
use Biz\Classroom\Service\ClassroomService;
use Biz\Course\Service\CourseService;
use Biz\Task\Service\TaskResultService;
use Biz\Task\Service\TaskService;
use Biz\Thread\Service\ThreadService;
use Biz\User\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;

class ClassroomController extends BaseController
{
    public function teachingAction(Request $request)
    {
        $user = $this->getCurrentUser();

        if (!$user->isTeacher()) {
            return $this->createMessageResponse('error', '您不是老师，不能查看此页面！');
        }

        $classrooms = $this->getClassroomService()->searchMembers(array('role' => 'teacher', 'userId' => $user->getId()), array('createdTime' => 'desc'), 0, PHP_INT_MAX);
        $classrooms = array_merge($classrooms, $this->getClassroomService()->searchMembers(array('role' => 'assistant', 'userId' => $user->getId()), array('createdTime' => 'desc'), 0, PHP_INT_MAX));
        $classroomIds = ArrayToolkit::column($classrooms, 'classroomId');

        $classrooms = $this->getClassroomService()->findClassroomsByIds($classroomIds);

        $members = $this->getClassroomService()->findMembersByUserIdAndClassroomIds($user->id, $classroomIds);

        foreach ($classrooms as $key => $classroom) {
            $courses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroom['id']);
            $courseIds = ArrayToolkit::column($courses, 'id');
            $coursesCount = count($courses);

            $classrooms[$key]['coursesCount'] = $coursesCount;

            $studentCount = $this->getClassroomService()->searchMemberCount(array('role' => 'student', 'classroomId' => $classroom['id'], 'startTimeGreaterThan' => strtotime(date('Y-m-d'))));
            $auditorCount = $this->getClassroomService()->searchMemberCount(array('role' => 'auditor', 'classroomId' => $classroom['id'], 'startTimeGreaterThan' => strtotime(date('Y-m-d'))));

            $allCount = $studentCount + $auditorCount;

            $classrooms[$key]['allCount'] = $allCount;

            $todayTimeStart = strtotime(date('Y-m-d', time()));
            $todayTimeEnd = strtotime(date('Y-m-d', time() + 24 * 3600));
            $todayFinishedTaskNum = 0;
            if (!empty($courseIds)) {
                $todayFinishedTaskNum = $this->getTaskResultService()->countTaskResults(array('courseIds' => $courseIds, 'createdTime' => $todayTimeStart, 'finishedTime' => $todayTimeEnd, 'status' => 'finish'));
            }


            $threadCount = $this->getThreadService()->searchThreadCount(array('targetType' => 'classroom', 'targetId' => $classroom['id'], 'type' => 'discussion', 'startTime' => $todayTimeStart, 'endTime' => $todayTimeEnd, 'status' => 'open'));

            $classrooms[$key]['threadCount'] = $threadCount;

            $classrooms[$key]['todayFinishedTaskNum'] = $todayFinishedTaskNum;
        }

        return $this->render('my/teaching/classroom.html.twig', array(
            'classrooms' => $classrooms,
            'members' => $members,
        ));
    }

    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getThreadService()
    {
        return $this->createService('Thread:ThreadService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }
}
