<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class ActivityController extends BaseController
{
    public function showAction($task, $preview, $mode, $doAgain)
    {
        $user = $this->getCurrentUser();
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        $member = $this->getCourseMemberService()->getCourseMember($lesson['courseId'], $user['id']);

        if ($member['role'] == 'teacher') {
            $this->getLessonRecordService()->changeLessonRecordByLessonId($lessonTask['lessonId'], $task['id']);
        }

        if (empty($activity)) {
            throw $this->createNotFoundException('activity not found');
        }
        $actionConfig = $this->getActivityConfig($activity['mediaType']);
        if ($member['role'] == 'teacher' && $lesson['status'] == 'created' && $lessonTask['stage'] == 'in') {
            return $this->render('activity/prompt.html.twig');
        } elseif ($member['role'] == 'student' && $lesson['status'] == 'teaching' && $lessonTask['stage'] == 'in' && !(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)) {
            return $this->render('activity/student-prompt.html.twig');
        }

        return $this->forward($actionConfig['controller'].':show', array(
            'activity' => $activity,
            'preview' => $preview,
            'mode' => $mode,
            'task' => $task,
            'doAgain' => $doAgain
        ));
    }

    public function collectBeforeTasksAction($courseId, $lessonId)
    {
        $lessonTasks = $this->getCourseLessonService()->findLessonTasksByLessonIdAndStage($lessonId, 'before');
        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');
        $tasks = $this->getTaskService()->findTasksByIds($taskIds);
        $tasks = ArrayToolkit::index($tasks, 'id');
        $results = $this->getCourseTaskResultService()->countStudentResultByTaskIds($taskIds);
        $results = ArrayToolkit::index($results, 'courseTaskId');
        $studentCounts = $this->getCourseMemberService()->countMembers(array(
            'courseId' => $courseId,
            'role' => 'student',
        ));

        $statistics = array();
        foreach ($tasks as $id => $task) {
            $count = empty($results[$id]) ? 0 : $results[$id]['count'];
            $statistics[] = array(
                'courseTaskId' => $id,
                'count' => $count,
                'title' => $task['title'],
                'unCount' => $studentCounts - $count,
                'rate' => $studentCounts > 0 ?  round($count / $studentCounts * 100) : 0,
            );
        }
        $this->getLessonRecordService()->changeLessonRecordByLessonId($lessonId, '0');

        return $this->render('activity/tasks-statistics/collectBeforeTask.html.twig', array(
            'statistics' => $statistics,
        ));
    }

    public function updateAction($id, $courseId, $stage)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $actionConfig = $this->getActivityConfig($activity['mediaType']);

        return $this->forward($actionConfig['controller'].':edit', array(
            'id' => $activity['id'],
            'courseId' => $courseId,
            'stage' => $stage,
        ));
    }

    public function createAction($type, $courseId, $stage)
    {
        $actionConfig = $this->getActivityConfig($type);

        return $this->forward($actionConfig['controller'].':create', array(
            'courseId' => $courseId,
            'stage' => $stage,
        ));
    }

    protected function getActivityConfig($type)
    {
        $config = $this->get('extension.manager')->getActivities();

        return $config[$type];
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getLessonRecordService()
    {
        return $this->createService('CustomBundle:Lesson:RecordService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getCourseTaskResultService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }
}
