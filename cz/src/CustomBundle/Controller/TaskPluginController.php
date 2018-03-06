<?php

namespace CustomBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use AppBundle\Controller\BaseController;

class TaskPluginController extends BaseController
{
    public function taskListAction(Request $request, $courseId, $lessonId, $taskId)
    {
        list($course) = $this->getCourseService()->tryTakeCourse($courseId);
        $user = $this->getCurrentUser();
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        $task = $this->getTaskService()->getTask($taskId);

        $preview = $request->query->get('preview', false);
        $courseMember = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);
        $teachingLesson = $this->getCourseLessonService()->getTeachingCourseLessonByCourseId($courseId);

        return $this->render('custom-task/plugin/task-list.html.twig', array(
            'role' => $courseMember['role'],
            'preview' => $preview,
            'lesson' => $lesson,
            'task' => $task,
            'teachingLesson' => $teachingLesson,
            'courseId' => $courseId,
            'user' =>$user,
        ));
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }
}
