<?php

namespace Biz\Task\Service\Impl;

use Biz\BaseService;
use Biz\Course\Service\MemberService;
use Biz\System\Service\LogService;
use Biz\System\Service\SettingService;
use Biz\Task\Dao\TaskDao;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\Task\Strategy\CourseStrategy;
use Biz\Task\Service\TaskResultService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseSetService;
use Biz\Activity\Service\ActivityService;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;

class TaskServiceImpl extends BaseService implements TaskService
{
    public function getTask($id)
    {
        return $this->getTaskDao()->get($id);
    }

    public function getCourseTask($courseId, $id)
    {
        $task = $this->getTaskDao()->get($id);
        if (empty($task) || $task['courseId'] != $courseId) {
            return array();
        }

        return $task;
    }

    public function getCourseTaskByCourseIdAndCopyId($courseId, $copyId)
    {
        $task = $this->getTaskDao()->getByCourseIdAndCopyId($courseId, $copyId);
        if (empty($task) || $task['courseId'] != $courseId) {
            return array();
        }

        return $task;
    }

    public function preCreateTaskCheck($task)
    {
        $this->getActivityService()->preCreateCheck($task['mediaType'], $task);
    }

    public function createTask($fields)
    {
        $fields = array_filter(
            $fields,
            function ($value) {
                if (is_array($value) || ctype_digit((string) $value)) {
                    return true;
                }

                return !empty($value);
            }
        );

        if ($this->invalidTask($fields)) {
            throw $this->createInvalidArgumentException('task is invalid');
        }

        if (!$this->getCourseService()->tryManageCourse($fields['fromCourseId'])) {
            throw $this->createAccessDeniedException('无权创建任务');
        }

        $this->preCreateTaskCheck($fields);

        $this->beginTransaction();
        try {
            if (isset($fields['content'])) {
                $fields['content'] = $this->purifyHtml($fields['content'], true);
            }

            $fields = $this->createActivity($fields);
            $strategy = $this->createCourseStrategy($fields['courseId']);
            $task = $strategy->createTask($fields);
            $this->getLogService()->info('course', 'add_task', "添加任务《{$task['title']}》({$task['id']})", $task);
            $this->dispatchEvent('course.task.create', new Event($task));
            $this->commit();

            return $task;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    protected function createActivity($fields)
    {
        $activity = $this->getActivityService()->createActivity($fields);

        $fields['activityId'] = $activity['id'];
        $fields['createdUserId'] = $activity['fromUserId'];
        $fields['courseId'] = $activity['fromCourseId'];
        $fields['type'] = $fields['mediaType'];
        $fields['endTime'] = $activity['endTime'];

        if ($activity['mediaType'] === 'video') {
            $media = json_decode($fields['media'], true);
            $fields['mediaSource'] = $media['source'];
        }

        return $fields;
    }

    protected function invalidTask($task)
    {
        if (!ArrayToolkit::requireds($task, array('title', 'fromCourseId'))) {
            return true;
        }

        return false;
    }

    public function preUpdateTaskCheck($taskId, $fields)
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new NotFoundException('task.not_found');
        }

        $this->getActivityService()->preUpdateCheck($task['activityId'], $fields);
    }

    public function updateTask($id, $fields)
    {
        $oldTask = $task = $this->getTask($id);

        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedException("can not update task #{$id}.");
        }

        $this->beginTransaction();
        try {
            $this->preUpdateTaskCheck($id, $fields);

            $activity = $this->getActivityService()->updateActivity($task['activityId'], $fields);

            if ($activity['mediaType'] === 'video') {
                $media = json_decode($fields['media'], true);
                $fields['mediaSource'] = $media['source'];
            }

            $fields['endTime'] = $activity['endTime'];
            $strategy = $this->createCourseStrategy($task['courseId']);
            $task = $strategy->updateTask($id, $fields);
            $this->getLogService()->info('course', 'update_task', "更新任务《{$task['title']}》({$task['id']})");
            $this->dispatchEvent('course.task.update', new Event($task, $oldTask));

            if ($task['type'] == 'download') {
                $this->dispatchEvent('course.task.material.update', new Event($task, $oldTask));
            }

            $this->commit();

            return $task;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function publishTask($id)
    {
        $task = $this->getTask($id);

        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedException("can not publish task #{$id}.");
        }

        if ($task['status'] === 'published') {
            throw $this->createAccessDeniedException("task(#{$task['id']}) has been published");
        }

        $strategy = $this->createCourseStrategy($task['courseId']);

        $task = $strategy->publishTask($task);
        $this->dispatchEvent('course.task.publish', new Event($task));

        return $task;
    }

    public function publishTasksByCourseId($courseId)
    {
        $this->getCourseService()->tryManageCourse($courseId);
        $tasks = $this->findTasksByCourseId($courseId);
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                if ($task['status'] !== 'published') {
                    //mode存在且不等于lesson的任务会随着mode=lesson的任务发布，这里不应重复发布
                    if (!empty($task['mode']) && $task['mode'] !== 'lesson') {
                        continue;
                    }
                    $this->publishTask($task['id']);
                }
            }
        }
    }

    public function unpublishTask($id)
    {
        $task = $this->getTask($id);

        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedException("can not unpublish task #{$id}.");
        }

        if ($task['status'] === 'unpublished') {
            throw $this->createAccessDeniedException("task(#{$task['id']}) has been unpublished");
        }

        $strategy = $this->createCourseStrategy($task['courseId']);
        $task = $strategy->unpublishTask($task);
        $this->dispatchEvent('course.task.unpublish', new Event($task));

        return $task;
    }

    public function updateSeq($id, $fields)
    {
        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'seq',
                'categoryId',
                'number',
            )
        );
        $task = $this->getTaskDao()->update($id, $fields);
        $this->dispatchEvent('course.task.update', new Event($task));

        return $task;
    }

    public function updateTasks($ids, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array('isFree'));

        foreach ($ids as $id) {
            $_task = $this->getTaskDao()->update($id, $fields);
            //xxx 这里可能影响执行效率：1. 批量处理，2. 仅仅是更新isFree，却会触发task的所有信息
            $this->dispatchEvent('course.task.update', new Event($_task));
        }

        return true;
    }

    public function deleteTask($id)
    {
        $task = $this->getTask($id);
        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedException('无权删除任务');
        }

        $this->beginTransaction();
        try {
            $result = $this->createCourseStrategy($task['courseId'])->deleteTask($task);

            $this->getLogService()->info('course', 'delete_task', "删除任务《{$task['title']}》({$task['id']})", $task);
            $this->dispatchEvent('course.task.delete', new Event($task, array('user' => $this->getCurrentUser())));

            $this->commit();

            return $result;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function findTasksByCourseId($courseId)
    {
        return $this->getTaskDao()->findByCourseId($courseId);
    }

    public function findTasksByCourseSetId($courseSetId)
    {
        return $this->getTaskDao()->findByCourseSetId($courseSetId);
    }

    public function findTasksByCourseIds($courseIds)
    {
        return $this->getTaskDao()->findByCourseIds($courseIds);
    }

    public function findTasksByActivityIds($activityIds)
    {
        $tasks = $this->getTaskDao()->findByActivityIds($activityIds);

        return ArrayToolkit::index($tasks, 'activityId');
    }

    public function countTasksByCourseId($courseId)
    {
        return $this->getTaskDao()->count(array('courseId' => $courseId));
    }

    public function findTasksByIds(array $ids)
    {
        return $this->getTaskDao()->findByIds($ids);
    }

    public function findTasksFetchActivityByCourseId($courseId)
    {
        $tasks = $this->findTasksByCourseId($courseId);
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

    public function findTasksFetchActivityAndResultByCourseId($courseId)
    {
        $tasks = $this->findTasksFetchActivityByCourseId($courseId);
        if (empty($tasks)) {
            return array();
        }

        return $this->wrapTaskResultToTasks($courseId, $tasks);
    }

    public function wrapTaskResultToTasks($courseId, $tasks)
    {
        $taskIds = array_column($tasks, 'id');
        $taskResults = $this->getTaskResultService()->findUserTaskResultsByTaskIds($taskIds);
        $taskResults = ArrayToolkit::index($taskResults, 'courseTaskId');

        array_walk(
            $tasks,
            function (&$task) use ($taskResults) {
                $task['result'] = isset($taskResults[$task['id']]) ? $taskResults[$task['id']] : null;
            }
        );

        $user = $this->getCurrentUser();
        $teacher = $this->getMemberService()->isCourseTeacher($courseId, $user->getId());

        $course = $this->getCourseService()->getCourse($courseId);
        $isLock = false;
        $magicSetting = $this->getSettingService()->get('magic');
        foreach ($tasks as &$task) {
            if ($course['learnMode'] == 'freeMode') {
                $task['lock'] = false;
            } else {
                $task = $this->setTaskLockStatus($tasks, $task, $teacher);
            }

            //设置第一个发布的任务为解锁的
            if (!$isLock && $task['status'] === 'published') {
                $task['lock'] = false;
                $isLock = true;
            }

            //计算剩余观看时长
            $shouldCalcWatchLimitRemaining = !empty($magicSetting['lesson_watch_limit']) && $task['type'] == 'video' && $task['mediaSource'] == 'self' && $course['watchLimit'];
            if ($shouldCalcWatchLimitRemaining) {
                if ($task['result']) {
                    $task['watchLimitRemaining'] = $course['watchLimit'] * $task['length'] - $task['result']['watchTime'];
                } else {
                    $task['watchLimitRemaining'] = $course['watchLimit'] * $task['length'];
                }
            }

            $isTryLookable = $course['tryLookable'] && $task['type'] == 'video' && !empty($task['ext']['file']) && $task['ext']['file']['storage'] === 'cloud';
            if ($isTryLookable) {
                $task['tryLookable'] = 1;
            } else {
                $task['tryLookable'] = 0;
            }
        }

        return $tasks;
    }

    protected function getPreTasks($tasks, $currentTask)
    {
        return array_filter(
            array_reverse($tasks),
            function ($task) use ($currentTask) {
                return $currentTask['seq'] > $task['seq'];
            }
        );
    }

    /**
     * 给定一个任务 ，判断前置解锁条件是完成.
     *
     * @param  $preTasks
     *
     * @return bool
     */
    public function isPreTasksIsFinished($preTasks)
    {
        $canLearnTask = true;

        foreach (array_values($preTasks) as $key => $preTask) {
            if ($preTask['status'] !== 'published') {
                continue;
            }
            if ($preTask['isOptional']) {
                $canLearnTask = true;
            }
            if ($preTask['type'] === 'live') {
                if (time() > $preTask['endTime']) {
                    continue;
                }
            }
            if ($preTask['type'] === 'testpaper' && $preTask['startTime']) {
                if (time() > $preTask['startTime'] + $preTask['activity']['ext']['limitedTime'] * 60) {
                    continue;
                }
            }

            $isTaskLearned = empty($preTask['result']) ? false : ($preTask['result']['status'] === 'finish');
            if ($isTaskLearned) {
                continue;
            } else {
                $canLearnTask = false;
                break;
            }
        }

        return $canLearnTask;
    }

    public function findUserTeachCoursesTasksByCourseSetId($userId, $courseSetId)
    {
        $conditions = array(
            'userId' => $userId,
        );
        $myTeachCourses = $this->getCourseService()->findUserTeachCourses($conditions, 0, PHP_INT_MAX, true);

        $conditions = array(
            'courseIds' => ArrayToolkit::column($myTeachCourses, 'courseId'),
            'courseSetId' => $courseSetId,
        );
        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            array('createdTime' => 'DESC'),
            0,
            PHP_INT_MAX
        );

        return $this->findTasksByCourseIds(ArrayToolkit::column($courses, 'id'));
    }

    public function searchTasks($conditions, $orderBy, $start, $limit)
    {
        return $this->getTaskDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function countTasks($conditions)
    {
        return $this->getTaskDao()->count($conditions);
    }

    public function startTask($taskId)
    {
        $task = $this->getTask($taskId);

        $user = $this->getCurrentUser();

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);

        if (!empty($taskResult)) {
            return;
        }

        $taskResult = array(
            'activityId' => $task['activityId'],
            'courseId' => $task['courseId'],
            'courseTaskId' => $task['id'],
            'userId' => $user['id'],
        );

        $taskResult = $this->getTaskResultService()->createTaskResult($taskResult);

        $this->dispatchEvent('course.task.start', new Event($taskResult));
    }

    public function doTask($taskId, $time = TaskService::LEARN_TIME_STEP)
    {
        $task = $this->tryTakeTask($taskId);

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);

        if (empty($taskResult)) {
            throw $this->createAccessDeniedException('task #{taskId} can not do. ');
        }

        $this->getTaskResultService()->waveLearnTime($taskResult['id'], $time);
    }

    public function watchTask($taskId, $watchTime = TaskService::WATCH_TIME_STEP)
    {
        $task = $this->tryTakeTask($taskId);

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);

        if (empty($taskResult)) {
            throw $this->createAccessDeniedException('task #{taskId} can not do. ');
        }

        $this->getTaskResultService()->waveWatchTime($taskResult['id'], $watchTime);
    }

    public function finishTask($taskId)
    {
        $this->tryTakeTask($taskId);

        if (!$this->isFinished($taskId)) {
            throw $this->createAccessDeniedException(
                "can not finish task #{$taskId}."
            );
        }

        return $this->finishTaskResult($taskId);
    }

    public function finishTaskResult($taskId)
    {
        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($taskId);

        if (empty($taskResult)) {
            $task = $this->getTask($taskId);
            $activity = $this->getActivityService()->getActivity($task['activityId']);
            if ($activity['mediaType'] === 'live') {
                $this->trigger($task['id'], 'start', array('task' => $task));
                $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($taskId);
            } else {
                throw $this->createAccessDeniedException('task access denied. ');
            }
        }

        if ($taskResult['status'] === 'finish') {
            return $taskResult;
        }

        $update['updatedTime'] = time();
        $update['status'] = 'finish';
        $update['finishedTime'] = time();
        $taskResult = $this->getTaskResultService()->updateTaskResult($taskResult['id'], $update);
        $this->dispatchEvent('course.task.finish', new Event($taskResult, array('user' => $this->getCurrentUser())));

        return $taskResult;
    }

    public function findFreeTasksByCourseId($courseId)
    {
        $tasks = $this->getTaskDao()->findByCourseIdAndIsFree($courseId, $isFree = true);
        $tasks = ArrayToolkit::index($tasks, 'id');

        return $tasks;
    }

    /**
     * 设置当前任务最大可同时进行的人数  如直播任务等.
     *
     * @param  $taskId
     * @param  $maxNum
     *
     * @return mixed
     */
    public function setTaskMaxOnlineNum($taskId, $maxNum)
    {
        return $this->getTaskDao()->update($taskId, array('maxOnlineNum' => $maxNum));
    }

    /**
     * 统计当前时间以后每天的直播次数.
     *
     * @param  $limit
     *
     * @return array <string, int|string>
     */
    public function findFutureLiveDates($limit = 4)
    {
        return $this->getTaskDao()->findFutureLiveDates($limit);
    }

    public function findPublishedLivingTasksByCourseSetId($courseSetId)
    {
        $conditions = array(
            'fromCourseSetId' => $courseSetId,
            'type' => 'live',
            'status' => 'published',
            'startTime_LT' => time(),
            'endTime_GT' => time(),
        );

        return $this->searchTasks($conditions, array('startTime' => 'ASC'), 0, $this->countTasks($conditions));
    }

    public function findPublishedTasksByCourseSetId($courseSetId)
    {
        $conditions = array(
            'fromCourseSetId' => $courseSetId,
            'type' => 'live',
            'status' => 'published',
        );

        return $this->searchTasks($conditions, array('startTime' => 'ASC'), 0, $this->countTasks($conditions));
    }

    /**
     * 返回当前正在直播的直播任务
     *
     * @return array
     */
    public function findCurrentLiveTasks()
    {
        $condition = array(
            'startTime_LE' => time(),
            'endTime_GT' => time(),
            'type' => 'live',
            'status' => 'published',
        );

        return $this->searchTasks($condition, array('startTime' => 'ASC'), 0, $this->countTasks($condition));
    }

    /**
     * 返回当前将要直播的直播任务
     *
     * @return array
     */
    public function findFutureLiveTasks()
    {
        $condition = array(
            'startTime_GT' => time(),
            'endTime_LT' => strtotime(date('Y-m-d').' 23:59:59'),
            'type' => 'live',
            'status' => 'published',
        );

        return $this->searchTasks($condition, array('startTime' => 'ASC'), 0, $this->countTasks($condition));
    }

    /**
     * 返回过去直播过的教学计划ID.
     *
     * @return array
     */
    public function findPastLivedCourseSetIds()
    {
        $arrays = $this->getTaskDao()->findPastLivedCourseSetIds();

        return ArrayToolkit::column($arrays, 'fromCourseSetId');
    }

    public function isFinished($taskId)
    {
        $task = $this->getTask($taskId);

        return $this->getActivityService()->isFinished($task['activityId']);
    }

    public function tryTakeTask($taskId)
    {
        if (!$this->canLearnTask($taskId)) {
            throw $this->createAccessDeniedException('the Task is Locked');
        }
        $task = $this->getTask($taskId);

        if (empty($task)) {
            throw $this->createNotFoundException('task does not exist');
        }

        return $task;
    }

    public function getNextTask($taskId)
    {
        $task = $this->getTask($taskId);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        $conditions = array(
            'courseId' => $task['courseId'],
            'status' => 'published',
        );
        if ($course['learnMode'] === 'freeMode') {
            $taskResults = $this->getTaskResultService()->findUserFinishedTaskResultsByCourseId($course['id']);
            $finishTaskIds = ArrayToolkit::column($taskResults, 'courseTaskId');
            $electiveTaskIds = $this->getStartElectiveTaskIds($course['id']);

            $conditions['excludeIds'] = array_merge($finishTaskIds, $electiveTaskIds);
        } else {
            if ($task['isOptional']) {
                $taskResults = $this->getTaskResultService()->findUserFinishedTaskResultsByCourseId($course['id']);
                $finishTaskIds = ArrayToolkit::column($taskResults, 'courseTaskId');
                $conditions['excludeIds'] = $finishTaskIds;
            } else {
                $conditions['seq_GT'] = $task['seq'];
            }
        }

        //取得下一个发布的课时
        $nextTasks = $this->getTaskDao()->search($conditions, array('seq' => 'ASC'), 0, 1);

        if (empty($nextTasks)) {
            return array();
        }
        $nextTask = array_shift($nextTasks);

        //判断下一个课时是否课时学习
        if (!$this->canLearnTask($nextTask['id'])) {
            return array();
        }

        return $nextTask;
    }

    public function canLearnTask($taskId)
    {
        $task = $this->getTask($taskId);

        $this->getCourseService()->tryTakeCourse($task['courseId']);

        //check if has permission to course and task
        $isAllowed = false;
        if ($task['isFree']) {
            $isAllowed = true;
        } elseif ($this->getCourseService()->canTakeCourse($task['courseId'])) {
            $isAllowed = true;
        }
        if ($isAllowed) {
            return $this->createCourseStrategy($task['courseId'])->canLearnTask($task);
        }

        return false;
    }

    public function isTaskLearned($taskId)
    {
        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($taskId);

        return empty($taskResult) ? false : ('finish' === $taskResult['status']);
    }

    public function getMaxSeqByCourseId($courseId)
    {
        return $this->getTaskDao()->getMaxSeqByCourseId($courseId);
    }

    public function getMaxNumberByCourseId($courseId)
    {
        return $this->getTaskDao()->getNumberSeqByCourseId($courseId);
    }

    public function getTaskByCourseIdAndActivityId($courseId, $activityId)
    {
        return $this->getTaskDao()->getTaskByCourseIdAndActivityId($courseId, $activityId);
    }

    public function findTasksByChapterId($chapterId)
    {
        return $this->getTaskDao()->findByChapterId($chapterId);
    }

    public function findTasksFetchActivityByChapterId($chapterId)
    {
        $tasks = $this->findTasksByChapterId($chapterId);

        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds);
        $activities = ArrayToolkit::index($activities, 'id');

        array_walk(
            $tasks,
            function (&$task) use ($activities) {
                $task['activity'] = $activities[$task['activityId']];
            }
        );

        return $tasks;
    }

    /**
     * @param  $courseId
     *
     * @return array tasks
     */
    public function findToLearnTasksByCourseId($courseId)
    {
        list($course) = $this->getCourseService()->tryTakeCourse($courseId);
        $toLearnTasks = $tasks = array();

        if (!in_array($course['learnMode'], array('freeMode', 'lockMode'))) {
            return $toLearnTasks;
        }

        if ($course['learnMode'] === 'freeMode') {
            $toLearnTask = $this->getToLearnTaskWithFreeMode($courseId);
            if (!empty($toLearnTask)) {
                $toLearnTasks[] = $toLearnTask;
            }
        }
        if ($course['learnMode'] === 'lockMode') {
            list($tasks, $toLearnTasks) = $this->getToLearnTasksWithLockMode($courseId);
            $toLearnTasks = $this->fillTaskResultAndLockStatus($toLearnTasks, $course, $tasks);
        }

        return $toLearnTasks;
    }

    /**
     * @param  $courseId
     *
     * @return array|mixed
     */
    public function findToLearnTasksByCourseIdForMission($courseId)
    {
        list($course) = $this->getCourseService()->tryTakeCourse($courseId);
        $toLearnTasks = $tasks = array();

        if (!in_array($course['learnMode'], array('freeMode', 'lockMode'))) {
            return $toLearnTasks;
        }
        list($tasks, $toLearnTasks) = $this->getToLearnTasksWithLockMode($courseId);

        $toLearnTasks = $this->fillTaskResultAndLockStatus($toLearnTasks, $course, $tasks);

        return $toLearnTasks;
    }

    protected function getToLearnTaskWithFreeMode($courseId)
    {
        $finishedTasks = $this->getTaskResultService()->findUserFinishedTaskResultsByCourseId($courseId);

        if (!empty($finishedTasks)) {
            $taskIds = ArrayToolkit::column($finishedTasks, 'courseTaskId');
            $electiveTaskIds = $this->getStartElectiveTaskIds($courseId);
            $taskIds = array_merge($taskIds, $electiveTaskIds);

            $conditions = array(
                'courseId' => $courseId,
                'status' => 'published',
                'excludeIds' => $taskIds,
            );

            $tasks = $this->searchTasks($conditions, array('seq' => 'ASC'), 0, 1);

            return empty($tasks) ? array() : array_shift($tasks);
        }

        $tasks = $this->findTasksByCourseId($courseId);

        return array_shift($tasks);
    }

    protected function getStartElectiveTaskIds($courseId)
    {
        $userTaskResults = $this->getTaskResultService()->findUserProgressingTaskResultByCourseId($courseId);
        $userTaskIds = ArrayToolkit::column($userTaskResults, 'courseTaskId');

        $conditions = array(
            'courseId' => $courseId,
            'status' => 'published',
            'isOptional' => 1,
        );

        $electiveTasks = $this->searchTasks($conditions, null, 0, PHP_INT_MAX);
        $electiveTaskIds = ArrayToolkit::column($electiveTasks, 'id');

        $electiveIds = array_intersect($userTaskIds, $electiveTaskIds);

        return empty($electiveIds) ? array() : $electiveIds;
    }

    protected function getToLearnTasksWithLockMode($courseId)
    {
        $toLearnTaskCount = 3;
        $taskResult = $this->getTaskResultService()->getUserLatestFinishedTaskResultByCourseId($courseId);
        $toLearnTasks = array();
        //取出所有的任务
        $taskCount = $this->countTasksByCourseId($courseId);
        $tasks = $this->getTaskDao()->search(array('courseId' => $courseId), array('seq' => 'ASC'), 0, $taskCount);
        if (empty($taskResult)) {
            $toLearnTasks = $this->getTaskDao()->search(
                array('courseId' => $courseId, 'status' => 'published'),
                array('seq' => 'ASC'),
                0,
                $toLearnTaskCount
            );

            return array($tasks, $toLearnTasks);
        }

        if (count($tasks) <= $toLearnTaskCount) {
            $toLearnTasks = $tasks;

            return array($tasks, $toLearnTasks);
        }

        $previousTask = null;
        //向后取待学习的三个任务
        foreach ($tasks as $task) {
            if ($task['id'] == $taskResult['courseTaskId']) {
                $toLearnTasks[] = $task;
                $previousTask = $task;
            }
            if ($previousTask && $task['seq'] > $previousTask['seq'] && count($toLearnTasks) < $toLearnTaskCount) {
                array_push($toLearnTasks, $task);
                $previousTask = $task;
            }
        }
        //向后去待学习的任务不足3个，向前取。
        $reverseTasks = array_reverse($tasks);
        if (count($toLearnTasks) < $toLearnTaskCount) {
            foreach ($reverseTasks as $task) {
                if ($task['id'] == $taskResult['courseTaskId']) {
                    $previousTask = $task;
                }
                if ($previousTask && $task['seq'] < $previousTask['seq'] && count($toLearnTasks) < $toLearnTaskCount) {
                    array_unshift($toLearnTasks, $task);
                    $previousTask = $task;
                }
            }
        }

        return array($tasks, $toLearnTasks);
    }

    public function trigger($id, $eventName, $data = array())
    {
        $task = $this->getTask($id);
        $data['task'] = $task;
        $this->getActivityService()->trigger($task['activityId'], $eventName, $data);

        return $this->getTaskResultService()->getUserTaskResultByTaskId($id);
    }

    public function sumCourseSetLearnedTimeByCourseSetId($courseSetId)
    {
        return $this->getTaskDao()->sumCourseSetLearnedTimeByCourseSetId($courseSetId);
    }

    public function analysisTaskDataByTime($startTime, $endTime)
    {
        return $this->getTaskDao()->analysisTaskDataByTime($startTime, $endTime);
    }

    /**
     * 获取用户最近进行的一个任务
     *
     * @param int $userId
     *
     * @return array
     */
    public function getUserRecentlyStartTask($userId)
    {
        $results = $this->getTaskResultService()->searchTaskResults(
            array(
                'userId' => $userId,
            ),
            array(
                'createdTime' => 'DESC',
            ),
            0,
            1
        );
        $result = array_shift($results);
        if (empty($result)) {
            return array();
        }

        return $this->getTask($result['courseTaskId']);
    }

    public function batchCreateTasks($tasks)
    {
        if (empty($tasks)) {
            return array();
        }

        return $this->getTaskDao()->batchCreate($tasks);
    }

    /**
     * @return TaskDao
     */
    protected function getTaskDao()
    {
        return $this->createDao('Task:TaskDao');
    }

    /**
     * @param  $courseId
     *
     * @throws \Codeages\Biz\Framework\Service\Exception\NotFoundException
     *
     * @return CourseStrategy
     */
    protected function createCourseStrategy($courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        if (empty($course)) {
            throw $this->createNotFoundException('course does not exist');
        }

        return $this->biz['course.strategy_context']->createStrategy($course['courseType']);
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->biz->service('Activity:ActivityService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->biz->service('Course:CourseService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->biz->service('Task:TaskResultService');
    }

    protected function getCourseMemberService()
    {
        return $this->biz->service('Course:MemberService');
    }

    /**
     * @param  $tasks
     * @param  $task
     * @param  $teacher
     *
     * @return mixed
     */
    protected function setTaskLockStatus($tasks, $task, $teacher)
    {
        //不是课程教师，无权限管理
        if ($teacher) {
            $task['lock'] = false;

            return $task;
        }

        $preTasks = $this->getPreTasks($tasks, $task);
        if (empty($preTasks)) {
            $task['lock'] = false;
        }

        $finish = $this->isPreTasksIsFinished($preTasks);
        //当前任务未完成且前一个问题未完成则锁定
        $task['lock'] = !$finish;

        //选修任务不需要判断解锁条件
        if ($task['isOptional']) {
            $task['lock'] = false;
        }

        if ($task['type'] === 'live') {
            $task['lock'] = false;
        }

        if ($task['type'] === 'testpaper' && $task['startTime']) {
            $task['lock'] = false;
        }

        //如果该任务已经完成则忽略其他的条件
        if (isset($task['result']['status']) && ($task['result']['status'] === 'finish')) {
            $task['lock'] = false;
        }

        return $task;
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @param  $toLearnTasks
     * @param  $course
     * @param  $tasks
     *
     * @return mixed
     */
    protected function fillTaskResultAndLockStatus($toLearnTasks, $course, $tasks)
    {
        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds, true);
        $activities = ArrayToolkit::index($activities, 'id');

        $taskIds = ArrayToolkit::column($tasks, 'id');
        $taskResults = $this->getTaskResultService()->findUserTaskResultsByTaskIds($taskIds);
        $taskResults = ArrayToolkit::index($taskResults, 'courseTaskId');

        array_walk(
            $tasks,
            function (&$task) use ($taskResults, $activities) {
                $task['result'] = isset($taskResults[$task['id']]) ? $taskResults[$task['id']] : null;
                $task['activity'] = $activities[$task['activityId']];
            }
        );

        $user = $this->getCurrentUser();
        $teacher = $this->getMemberService()->isCourseTeacher($course['id'], $user->getId());

        //设置任务是否解锁
        foreach ($toLearnTasks as &$toLearnTask) {
            $toLearnTask['activity'] = $activities[$toLearnTask['activityId']];
            $toLearnTask['result'] = isset($taskResults[$toLearnTask['id']]) ? $taskResults[$toLearnTask['id']] : null;
            if ($course['learnMode'] === 'lockMode') {
                $toLearnTask = $this->setTaskLockStatus($tasks, $toLearnTask, $teacher);
            }
        }

        return $toLearnTasks;
    }

    /**
     * @return MemberService
     */
    protected function getMemberService()
    {
        return $this->createService('Course:MemberService');
    }
}
