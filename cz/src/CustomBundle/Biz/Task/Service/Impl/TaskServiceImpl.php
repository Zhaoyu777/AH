<?php

namespace CustomBundle\Biz\Task\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Task\Service\TaskService;
use Biz\Task\Service\Impl\TaskServiceImpl as BaseTaskServiceImpl;

class TaskServiceImpl extends BaseTaskServiceImpl implements TaskService
{
    public function startInstantCourseTask($taskId)
    {
        $task = $this->getTask($taskId);

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig($activity['mediaType']);
        $realActivity = $config->get($activity['mediaId']);

        $this->beginTransaction();
        try {
            $status = $this->getStatusService()->getStatusByTaskId($taskId);
            $affected = $this->getStatusService()->startTask($task['id'], $task['activityId']);

            if ((!empty($realActivity['groupWay'])) && empty($status)) {
                if ($realActivity['groupWay'] == 'random') {
                    $this->getTaskGroupService()->createRandomTaskGroups($taskId, $realActivity['groupNumber']);
                } elseif ($realActivity['groupWay'] == 'fixed') {
                    $this->copyFixedGroupToTaskGroup($task);
                } else {
                    $this->createNoneTaskGroup($task);
                }
            }

            $this->commit();

            $this->dispatchEvent('push.task.start', new Event($affected));

            return $affected;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    private function createNoneTaskGroup($task)
    {
        $taskGroup = $this->getTaskGroupService()->createTaskGroup(array(
            'title' => '不分组',
            'taskId' => $task['id'],
        ));

        $members = $this->getCourseMemberService()->findCourseStudents($task['courseId'], 0, PHP_INT_MAX);
        foreach ($members as $key => $member) {
            $this->getTaskGroupService()->createTaskGroupMember(array(
                'groupId' => $taskGroup['id'],
                'seq' => empty($member['seq']) ? 1 : $member['seq'],
                'userId' => $member['userId'],
                'taskId' => $task['id'],
            ));
        }
    }

    private function copyFixedGroupToTaskGroup($task)
    {
        $groups = $this->getFixedGroupService()->findCourseGroupsByCourseIdWithMembers($task['courseId'], true);
        foreach ($groups as $key => $group) {
            $taskGroup = $this->getTaskGroupService()->createTaskGroup(array(
                'title' => $group['title'],
                'taskId' => $task['id'],
            ));

            foreach ($group['members'] as $key => $member) {
                $this->getTaskGroupService()->createTaskGroupMember(array(
                    'groupId' => $taskGroup['id'],
                    'seq' => empty($member['seq']) ? 1 : $member['seq'],
                    'userId' => $member['userId'],
                    'taskId' => $task['id'],
                ));
            }
        }
    }

    public function getTaskByCategoryId($categoryId)
    {
        return $this->getTaskDao()->getByCategoryId($categoryId);
    }

    public function findTasksByCategoryIds($categoryIds)
    {
        if (empty($categoryIds)) {
            return array();
        }

        return $this->getTaskDao()->findByCategoryIds($categoryIds);
    }

    public function getFirstInClassTaskByLessonId($lessonId)
    {
        $chapter = $this->getCourseService()->getFirstInClassTaskChapterByLessonId($lessonId);

        if (empty($chapter)) {
            return array();
        }

        return $this->getTaskByCategoryId($chapter['id']);
    }

    public function getFirstClassTaskByLessonId($lessonId)
    {
        $chapter = $this->getCourseService()->getFirstClassTaskChapterByLessonId($lessonId);

        if (empty($chapter)) {
            return array();
        }

        return $this->getTaskByCategoryId($chapter['id']);
    }

    public function getFirstBeforeClassTaskByLessonId($lessonId)
    {
        $chapter = $this->getCourseService()->getFirstBeforeClassTaskChapterByLessonId($lessonId);

        if (empty($chapter)) {
            return array();
        }

        return $this->getTaskByCategoryId($chapter['id']);
    }

    public function getCurrentTaskByLessonId($lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        $user = $this->getCurrentUser();
        if ($lesson['status'] == 'teached') {
            $chapter = $this->getCourseService()->getFirstUnFinishedAfterClassTaskChapterByLessonId($lessonId, $user['id']);
            $unStartTask = $this->getFirstNotStartedAfterTaskByLessonId($lessonId, $user['id']);
        } else {
            $chapter = $this->getCourseService()->getFirstUnFinishedBeforeClassTaskChapterByLessonId($lessonId, $user['id']);
            $unStartTask = $this->getFirstNotStartedBeforeTaskByLessonId($lessonId, $user['id']);
        }

        return $this->contrastFirstTask($lessonId, $chapter, $unStartTask);
    }

    public function contrastFirstTask($lessonId, $chapter, $unStartTask)
    {
        if (empty($chapter) && empty($unStartTask)) {
            $inClassChapter = $this->getCourseService()->getFirstInClassTaskChapterByLessonId($lessonId);
            return empty($inClassChapter) ? null:$this->getTaskByCategoryId($inClassChapter['id']);
        }
        if (empty($chapter)) {
            return $unStartTask;
        }
        $unFinishedTask = $this->getTaskByCategoryId($chapter['id']);
        if (empty($unStartTask)) {
            return $unFinishedTask;
        }
        if ($unStartTask['seq'] > $unFinishedTask['seq']) {
            return $unFinishedTask;
        }
        return $unStartTask;
    }

    public function getFirstNotStartedBeforeTaskByLessonId($lessonId, $userId)
    {
        return $this->getTaskDao()->getFirstNotStartedBeforeTaskByLessonId($lessonId, $userId);
    }

    public function getFirstNotStartedAfterTaskByLessonId($lessonId, $userId)
    {
        return $this->getTaskDao()->getFirstNotStartedAfterTaskByLessonId($lessonId, $userId);
    }

    public function findTasksFetchActivityByTaskIds($taskIds)
    {
        $tasks =  $this->findTasksByIds($taskIds);
        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds, true);
        $activities = ArrayToolkit::index($activities, 'id');

        array_walk(
            $tasks,
            function (&$task) use ($activities) {
                $activity = $activities[$task['activityId']];
                $task['activity'] = $activity;
            }
        );

        return $tasks;
    }

    public function findTasksFetchActivityAndResultsByTaskIds($taskIds)
    {
        $tasks = $this->findTasksFetchActivityByTaskIds($taskIds);
        if (empty($tasks)) {
            return array();
        }
        $taskResults = $this->getTaskResultService()->findUserTaskResultsByTaskIds($taskIds);
        $taskResults = ArrayToolkit::index($taskResults, 'courseTaskId');

        array_walk(
            $tasks,
            function (&$task) use ($taskResults) {
                $task['result'] = isset($taskResults[$task['id']]) ? $taskResults[$task['id']] : null;
            }
        );
        $isLock = false;
        foreach ($tasks as &$task) {
            $task = $this->setTaskLockStatus($tasks, $task, 1);
            //设置第一个发布的任务为解锁的
            if ($task['status'] == 'published' && !$isLock) {
                $task['lock'] = false;
                $isLock = true;
            }
        }

        return $tasks;
    }

    public function findTaskResultsByUserIdsAndTaskId($userIds, $taskId)
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->getTaskResultDao()->findByUserIdsAndTaskId($userIds, $taskId);
    }

    public function getLatestTaskResultByCourseIdAndUserIds($courseId, $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->getTaskResultDao()->getLatestByCourseIdAndUserIds($courseId, $userIds);
    }

    public function findResultsByTaskIdsAndUserIds($taskIds, $userIds)
    {
        if (empty($taskIds) || empty($userIds)) {
            return array();
        }

        return $this->getTaskResultDao()->findByTaskIdsAndUserIds($taskIds, $userIds);
    }

    public function findResultsByTime($startTime, $endTime)
    {
        return $this->getTaskResultDao()->findByTime($startTime, $endTime);
    }

    public function getCurrentTaskByCourseIdAndUserIds($courseId, $userIds)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin() || empty($userIds)) {
            return array();
        }

        $result = $this->getTaskResultDao()->getLatestByCourseIdAndUserIds($courseId, $userIds);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        if ($lesson['status'] != 'teaching') {
            return array();
        }

        $task = $this->getTaskDao()->get($result['courseTaskId']);

        $task = array(
            'taskId' => $result['courseTaskId'],
            'activityId' => $task['activityId'],
            'activityTitle' => $task['title'],
            'activityType' => $task['type'],
            'lessonTitle' => empty($lesson['title']) ? '课次'.$lesson['number'] : $lesson['title'],
            'lessonId' => $lesson['id'],
        );

        return $task;
    }

    public function deleteTaskResultsByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return;
        }
        return $this->getTaskResultDao()->deleteByTaskIds($taskIds);
    }

    public function countStudentResultByTaskId($taskId)
    {
        return $this->getTaskResultDao()->countStudentResultByTaskId($taskId);
    }

    public function countStudentResultByTaskIds($taskIds)
    {
        $results = $this->getTaskResultDao()->countStudentResultByTaskIds($taskIds);

        $resultTaskIds = ArrayToolkit::column($results, 'courseTaskId');
        $diffTaskIds = array_diff($taskIds, $resultTaskIds);

        foreach ($diffTaskIds as $taskId) {
            $results[] = array(
                'courseTaskId' => $taskId,
                'count' => 0
            );
        }

        return $results;
    }

    public function findCountStudentResultByTaskIds($taskIds)
    {
        $taskResults = $this->getTaskResultDao()->findStudentResultByTaskIds($taskIds);
        if (empty($taskResults)) {
            return array();
        }

        $taskResult = reset($taskResults);
        $teacherIds = $this->getCourseMemberService()->findCourseTeachers($taskResult['courseId']);
        $teacherIds = ArrayToolkit::column($teacherIds, 'userId');
        foreach ($taskResults as $key => $taskResult) {
            if (in_array($taskResult['userId'], $teacherIds)) {
                unset($taskResults[$key]);
            }
        }

        $taskResults = ArrayToolkit::group($taskResults, 'courseTaskId');

        $reset = array();
        foreach ($taskResults as $courseTaskId => $taskResult) {
            $reset[] = array(
                'courseTaskId' => $courseTaskId,
                'count' => count($taskResult),
            );
        }

        return $reset;
    }

    public function countTaskByCourseIdsAndType($courseIds, $type)
    {
        return $this->getTaskDao()->count(array(
            'courseIds' => $courseIds,
            'type' => $type
        ));
    }

    public function countStudentResultByTaskIdAndStatus($taskId, $status)
    {
        return $this->getTaskResultDao()->count(array(
            'courseTaskId' => $taskId,
            'status' => $status,
        ));
    }

    public function countCompleteStudentResultByTaskId($taskId)
    {
        $task = $this->tryTakeTask($taskId);
        $teachers = $this->getCourseMemberService()->findCourseTeachers($task['courseId']);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');

        return $this->getTaskResultDao()->countStudentResultByTaskIdAndStatusAndTeacherIds($taskId, 'finish', $teacherIds);
    }

    public function findInteractiveTaskByIds($taskIds)
    {
        return $this->getTaskDao()->findInteractiveTaskByIds($taskIds);
    }

    public function findStatisticsTaskCountByUserId($userId)
    {
        return $this->getTaskDao()->findStatisticsTaskCountByUserId($userId);
    }

    public function findActiveTasksResultsByCourseId($courseId)
    {
        $teachers = $this->getCourseMemberService()->findCourseTeachers($courseId);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');

        if (empty($teacherIds)) {
            return array();
        }

        return $this->getTaskResultDao()->findResultsByCourseIdAndUserIds($courseId, $teacherIds);
    }

    public function getNextTask($taskId)
    {
        return $this->getCloseToTask($taskId, 'next');
    }

    public function getPreviousTask($taskId)
    {
        return $this->getCloseToTask($taskId, 'last');
    }

    public function getCloseToTask($taskId, $type)
    {
        $baseTask = $this->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $baseChapter = $this->getCourseChapterDao()->get($baseTask['categoryId']);
        $user = $this->getCurrentUser();
        $isTeacher = $this->getCourseMemberService()->isCourseTeacher($baseChapter['courseId'], $user['id']);
        if ($isTeacher || $lesson['status'] == 'teached') {
            $chapters = $this->getCourseChapterDao()->findChapterByCourseIdAndLessonId($baseChapter['courseId'], $baseChapter['lessonId']);
        } else {
            $chapters = $this->getCourseChapterDao()->findChapterByLessonIdAndStage($baseChapter['lessonId'], 'before');
        }

        $number = 0;
        foreach ($chapters as $key => $chapter) {
            if ($chapter['id'] == $baseChapter['id']) {
                $number = $type == 'next' ? $key + 1 :  $key - 1;
                break ;
            }
        }

        if ($number > count($chapters) - 1 || $number < 0) {
            return array();
        }

        return $this->getTaskByCategoryId($chapters[$number]['id']);
    }

    public function countFrontTaskByTaskId($taskId)
    {
        $task = $this->getTask($taskId);
        $baseChapter = $this->getCourseChapterDao()->get($task['categoryId']);
        $count = $this->getCourseChapterDao()->countChapterByLessonIdAndSeqAndStage($baseChapter['lessonId'], $baseChapter['seq'], 'before') + 1;
        if ($baseChapter['stage'] == 'before') {
            return $count;
        }
        $beforeCount = $this->getCourseChapterDao()->countChapterByLessonIdAndStage($baseChapter['lessonId'], 'before');

        $count = $this->getCourseChapterDao()->countChapterByLessonIdAndSeqAndStage($baseChapter['lessonId'], $baseChapter['seq'], 'in') + 1;
        if ($baseChapter['stage'] ==  'in') {
            return $beforeCount + $count;
        }

        $inCount = $this->getCourseChapterDao()->countChapterByLessonIdAndStage($baseChapter['lessonId'], 'in');
        $count = $this->getCourseChapterDao()->countChapterByLessonIdAndStage($baseChapter['lessonId'], $baseChapter['seq'], 'after') + 1;

        return $beforeCount + $inCount + $count;
    }

    public function findTasksFetchActivityBylessonId($lessonId)
    {
        $lessonTasks = $this->getCourseLessonService()->findLessonTasksByLessonId($lessonId);
        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');
        $tasks = $this->findTasksByIds($taskIds);
        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds, true);
        $activities = ArrayToolkit::index($activities, 'id');

        array_walk(
            $tasks,
            function (&$task) use ($activities) {
                $task['activity'] = $activities[$task['activityId']];
            }
        );

        return $tasks;
    }

    public function countByTaskType($taskType)
    {
        return count($this->getTaskDao()->findTasksByTaskType($taskType));
    }

    public function findTasksByIdsAndTypes($taskIds, $types)
    {
        if (empty($taskIds)) {
            return array();
        }

        return $this->getTaskDao()->findTasksByIdsAndTypes($taskIds, $types);
    }

    protected function getCourseChapterDao()
    {
        return $this->createDao('CustomBundle:Course:CourseChapterDao');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getTaskResultDao()
    {
        return $this->createDao('CustomBundle:Task:ResultDao');
    }

    protected function getFixedGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getTaskDao()
    {
        return $this->createDao('CustomBundle:Task:TaskDao');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
}
