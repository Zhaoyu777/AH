<?php

namespace CustomBundle\Biz\Activity\Event;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Codeages\PluginBundle\Event\EventSubscriber;
use CustomBundle\Biz\Task\Service\TaskService;
use CustomBundle\Biz\Course\Service\CourseLessonService;

class CustomActivitySubscriber extends EventSubscriber
{
    public static function getSubscribedEvents()
    {
        return array(
            'rollcall.remark' => 'onRollcallRemark',
            'display.wall.remark' => 'onDisplayWallRemark',
            'rollcall.result.delete' => 'onRollcallResultDelete',
            'display.wall.result.delete' => 'onRollcallWallResultDelete',
            'display.wall.content.delete' => 'onRollcallWallContentDelete',
            'race.answer.remark' => 'onRaceAnswerRemark',
            'brain.storm.remark' => 'onBraninStormRemark',
            'brain_storm.create' => 'onSpecialActivityCreate',
            'display.wall.create' => 'onSpecialActivityCreate',
            'race.answer.delete' => 'onRaceAnswerDelete',
            'random.testpaper.create' => 'onRandomTestpaperCreate',
            //'brain_storm.create' => 'onSpecialActivityCreate',
            //'display.wall.create' => 'onSpecialActivityCreate',
            'practice.remark' => 'onRollcallRemark',
            'practice.result.delete' => 'onPracticeResultDelete',
        );
    }

    public function onSpecialActivityCreate(Event $event)
    {
        try {
            $result = $event->getSubject();
            $task = $this->getTaskService()->getTask($result['courseTaskId']);
            $activity = $this->getActivityService()->getActivity($result['activityId']);
            $config = $this->getActivityService()->getActivityConfig($task['type']);
            $activity = $config->get($activity['mediaId']);

            if ($activity['submitWay'] == 'person') {
                $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($result['courseTaskId']);
                if (!empty($taskResult)) {
                    $this->getTaskResultService()->updateTaskResult($taskResult['id'], array('status' => 'finish'));
                }
            } else {
                $groupMembers = $this->getTaskGroupService()->findTaskGroupMembersByGroupId($result['groupId']);
                $userIds = ArrayToolkit::column($groupMembers, 'userId');

                $this->getTaskResultService()->updateGroupTaskResultByTaskIdAndUserIds($result['courseTaskId'], $userIds);
            }
        } catch (\Exception $e) {

        }
    }

    public function onRollcallRemark(Event $event)
    {
        $result = $event->getSubject();
        $course = $this->getCourseService()->getCourse($result['courseId']);
        $task = $this->getTaskService()->getTask($result['courseTaskId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
        $courseLesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $score = array(
            'courseId' => $result['courseId'],
            'lessonId' => $lessonTask['lessonId'],
            'taskId' => $task['id'],
            'type' => 'operate',
            'term' => empty($course['termCode']) ? '' : $course['termCode'],
            'userId' => $result['userId'],
            'score' => $result['score'],
            'targetType' => 'rollcall',
            'targetId' => $result['id'],
            'remark' => '课次'.$courseLesson['number'].' - '.$task['title'],
        );

        $this->getScoreService()->createScore($score);
    }

    public function onRandomTestpaperCreate(Event $event)
    {
        $testpaper = $event->getSubject();
        if ($testpaper['doTime'] == 1) {
            $course = $this->getCourseService()->getCourse($testpaper['courseId']);
            $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($testpaper['taskId']);
            $taskResult = $this->getTaskResultService()->updateTaskResult($taskResult['id'], array('status' => 'finish'));
            if ($course['type'] == 'instant') {
                $activity = $this->getActivityService()->getActivity($testpaper['activityId']);
                if ($activity['score'] > 0) {
                    $courseLesson = $this->getCourseLessonService()->getCourseLesson($testpaper['lessonId']);
                    $task = $this->getTaskService()->getTask($testpaper['taskId']);
                    $score = array(
                        'courseId' => $testpaper['courseId'],
                        'taskId' => $testpaper['taskId'],
                        'lessonId' => $testpaper['lessonId'],
                        'type' => 'auto',
                        'term' => empty($course['termCode']) ? '' : $course['termCode'],
                        'score' => $activity['score'],
                        'targetType' => 'randomTestpaper',
                        'targetId' => $testpaper['id'],
                        'remark' => '课次'.$courseLesson['number'].' - '.$task['title'],
                        'userId' => $testpaper['userId'],
                    );
                    $this->getScoreService()->createScore($score);
                }
            }
        }
    }

    public function onBraninStormRemark(Event $event)
    {
        $result = $event->getSubject();
        $course = $this->getCourseService()->getCourse($result['courseId']);
        $task = $this->getTaskService()->getTask($result['courseTaskId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
        $courseLesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $brainStorm = $config->get($activity['mediaId']);

        $score = array(
            'courseId' => $result['courseId'],
            'taskId' => $task['id'],
            'lessonId' => $courseLesson['id'],
            'type' => 'operate',
            'term' => empty($course['termCode']) ? '' : $course['termCode'],
            'score' => $result['score'],
            'targetType' => 'brainStorm',
            'targetId' => $result['id'],
            'remark' => '课次'.$courseLesson['number'].' - '.$task['title'],
        );

        if ($brainStorm['submitWay'] == 'person') {
            $score['userId'] = $result['userId'];
            $this->getScoreService()->createScore($score);
        } else {
            $groupMembers = $this->getTaskGroupService()->findTaskGroupMembersByGroupId($result['groupId']);
            foreach ($groupMembers as $groupMember) {
                $score['userId'] = $groupMember['userId'];
                $this->getScoreService()->createScore($score);
            }
        }
    }

    public function onDisplayWallRemark(Event $event)
    {
        $result = $event->getSubject();
        $course = $this->getCourseService()->getCourse($result['courseId']);
        $task = $this->getTaskService()->getTask($result['courseTaskId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
        $courseLesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $displayWall = $config->get($activity['mediaId']);

        $score = array(
            'courseId' => $result['courseId'],
            'taskId' => $task['id'],
            'lessonId' => $courseLesson['id'],
            'type' => 'operate',
            'term' => empty($course['termCode']) ? '' : $course['termCode'],
            'score' => $result['score'],
            'targetType' => 'displayWall',
            'targetId' => $result['id'],
            'taskId' => $task['id'],
            'remark' => '课次'.$courseLesson['number'].' - '.$task['title'],
        );

        if ($displayWall['submitWay'] == 'person') {
            $score['userId'] = $result['userId'];
            $this->getScoreService()->createScore($score);
        } else {
            $groupMembers = $this->getTaskGroupService()->findTaskGroupMembersByGroupId($result['groupId']);
            foreach ($groupMembers as $groupMember) {
                $score['userId'] = $groupMember['userId'];
                $this->getScoreService()->createScore($score);
            }
        }
    }

    public function onRaceAnswerRemark(Event $event)
    {
        $result = $event->getSubject();
        $course = $this->getCourseService()->getCourse($result['courseId']);
        $task = $this->getTaskService()->getTask($result['courseTaskId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
        $courseLesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $score = array(
            'courseId' => $result['courseId'],
            'taskId' => $task['id'],
            'lessonId' => $courseLesson['id'],
            'type' => 'operate',
            'term' => empty($course['termCode']) ? '' : $course['termCode'],
            'userId' => $result['userId'],
            'score' => $result['score'],
            'targetType' => 'raceAnswer',
            'targetId' => $result['id'],
            'remark' => '课次'.$courseLesson['number'].' - '.$task['title'],
        );

        $this->getScoreService()->createScore($score);
    }

    public function onRollcallResultDelete(Event $event)
    {
        $result = $event->getSubject();

        $this->getScoreService()->deleteScoreByTargetTypeAndTargetId('rollcall', $result['id']);
    }

    public function onRollcallWallResultDelete(Event $event)
    {
        $result = $event->getSubject();

        $this->getScoreService()->deleteScoreByTargetTypeAndTargetId('displayWall', $result['id']);

        $this->getDisplayWallResultService()->deleteContentsByResultId($result['id']);
    }

    public function onPracticeResultDelete(Event $event)
    {
        $result = $event->getSubject();

        $this->getScoreService()->deleteScoreByTargetTypeAndTargetId('practice', $result['id']);

        //$this->getPracticeResultService()->deleteContentsByResultId($result['id']);
    }

    public function onRollcallWallContentDelete(Event $event)
    {
        $content = $event->getSubject();

        $this->getDisplayWallResultService()->deletePostsByContentId($content['id']);

        $this->getDisplayWallResultService()->deleteLikesByContentId($content['id']);
    }

    public function onRaceAnswerDelete(Event $event)
    {
        $result = $event->getSubject();

        $this->getScoreService()->deleteScoreByTargetTypeAndTargetId('raceAnswer', $result['id']);
    }

    protected function getDisplayWallResultService()
    {
        return $this->getBiz()->service('CustomBundle:DisplayWall:ResultService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseService');
    }

    protected function getTaskGroupService()
    {
        return $this->getBiz()->service('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    protected function getScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:ScoreService');
    }

    protected function getTaskService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }

    protected function getCourseLessonService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getRaceAnswerService()
    {
        return $this->getBiz()->service('CustomBundle:raceAnswer:RaceAnswerService');
    }

    protected function getTaskResultService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskResultService');
    }
}
