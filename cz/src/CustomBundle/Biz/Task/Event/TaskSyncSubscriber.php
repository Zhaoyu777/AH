<?php

namespace CustomBundle\Biz\Task\Event;

use Biz\Task\Dao\TaskDao;
use Biz\Activity\Config\Activity;
use Biz\Activity\Dao\ActivityDao;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Biz\Task\Strategy\StrategyContext;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Event\CourseSyncSubscriber;
use Biz\Course\Copy\Impl\ActivityTestpaperCopy;

class TaskSyncSubscriber extends CourseSyncSubscriber
{
    public static function getSubscribedEvents()
    {
        return array(
            'course.task.delete' => 'onCourseTaskDelete',
            'course.task.finish' => 'onTaskFinish',
            'exam.finish' => 'onTestpaperFinish',
        );
    }

    public function onCourseTaskDelete(Event $event)
    {
        $task = $event->getSubject();
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);

        $this->getCourseLessonService()->deleteLessonTask($lessonTask['id']);
        $course = $this->getCourseService()->getCourse($lessonTask['courseId']);

        if (!empty($task['categoryId']) && $course['type'] == 'instant') {
            $this->getCourseService()->deleteChapter($task['courseId'], $task['categoryId']);
        }
    }

    public function onTaskFinish(Event $event)
    {
        $taskResult = $event->getSubject();

        $this->createAutoScore($taskResult);
    }

    public function onTestpaperFinish(Event $event)
    {
        try {
            $paperResult = $event->getSubject();

            $biz = $this->getBiz();
            $user = $biz['user'];
            $activity = $this->getActivityService()->getActivity($paperResult['lessonId']);
            $task = $this->getTaskService()->getTaskByCourseIdAndActivityId($paperResult['courseId'], $activity['id']);
            $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);
            $taskResult = $this->getTaskService()->finishTaskResult($task['id']);
            $this->createAutoScore($taskResult);
        } catch (\Exception $e) {
        }
    }

    public function createAutoScore($taskResult)
    {
        $activity = $this->getActivityService()->getActivity($taskResult['activityId']);
        $score = $this->getScoreService()->getScoreByTaskIdAndUserId($taskResult['courseTaskId'], $taskResult['userId']);
        if ($activity['score'] > 0 && empty($score)) {
            $course = $this->getCourseService()->getCourse($taskResult['courseId']);
            if ($course['type'] != 'instant') {
                return ;
            }

            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskResult['courseTaskId']);
            $courseLesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
            $score = array(
                'courseId' => $taskResult['courseId'],
                'lessonId' => $lessonTask['lessonId'],
                'taskId' => $taskResult['courseTaskId'],
                'type' => 'auto',
                'term' => empty($course['termCode']) ? '' : $course['termCode'],
                'userId' => $taskResult['userId'],
                'score' => $activity['score'],
                'targetType' => 'course_task_result',
                'targetId' => $taskResult['courseTaskId'],
                'remark' => '课次'.$courseLesson['number'].' - '.$activity['title'],
            );

            $this->getScoreService()->createScore($score);
        }
    }

    protected function getActivityService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:ActivityService');
    }

    protected function getScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:ScoreService');
    }

    protected function getCourseLessonService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseService');
    }

    protected function getTaskResultService()
    {
        return $this->getBiz()->service('Task:TaskResultService');
    }

    protected function getTaskService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }
}
