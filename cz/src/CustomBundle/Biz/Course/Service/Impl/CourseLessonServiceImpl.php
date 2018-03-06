<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\Task\Strategy\StrategyContext;
use CustomBundle\Common\BeanstalkClient;
use CustomBundle\Biz\Course\Service\CourseLessonService;

class CourseLessonServiceImpl extends BaseService implements CourseLessonService
{
    public function createCourseLesson($lesson)
    {
        if (!ArrayToolkit::requireds($lesson, array('courseId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $lesson = ArrayToolkit::parts($lesson, array(
            'courseId',
            'number',
            'seq',
            'title',
        ));

        $this->beginTransaction();
        try {
            $created = $this->getCourseLessonDao()->create($lesson);

            $this->commit();

            $this->dispatchEvent('courseLesson.create', new Event($created));

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function batchCreateCourseLessons($courseId, $count)
    {
        $lesson = array(
            'courseId' => $courseId,
        );

        for ($i = 1; $i < $count + 1; ++$i) {
            $lesson['number'] = $i;
            $created = $this->createCourseLesson($lesson);
        }

        return $created;
    }

    public function updateCourseLesson($id, $fields)
    {
        $lesson = $this->getCourseLesson($id);

        if (empty($lesson)) {
            throw $this->createNotFoundException('lesson not found');
        }

        $fields = ArrayToolkit::parts($fields, array(
            'title',
            'teachAim',
            'abilityAims',
            'knowledgeAims',
            'qualityAims',
            'tasksCase',
            'difficult',
            'referenceMaterial',
            'afterKnow',
        ));

        list(
            $fields,
            $notInFields,
        ) = $this->splitFields($fields);

        $result = $this->getCourseLessonDao()->update($id, $fields);

        $this->dispatchEvent('course.lesson.update', new Event($result, array(
            'fields' => $notInFields,
        )));

        return $result;
    }

    public function taskNumByLessonId($lessonId, $taskNum)
    {
        $this->getCourseLessonDao()->update($lessonId, array('taskNum' => $taskNum));
    }

    protected function splitFields($fields)
    {
        $notInFields = array();

        if (isset($fields['abilityAims'])) {
            $notInFields['abilityAims'] = $fields['abilityAims'];
            unset($fields['abilityAims']);
        }

        if (isset($fields['knowledgeAims'])) {
            $notInFields['knowledgeAims'] = $fields['knowledgeAims'];
            unset($fields['knowledgeAims']);
        }

        if (isset($fields['qualityAims'])) {
            $notInFields['qualityAims'] = $fields['qualityAims'];
            unset($fields['qualityAims']);
        }

        return array(
            $fields,
            $notInFields,
        );
    }

    public function startCourseLesson($id)
    {
        $lesson = $this->getCourseLesson($id);

        if (empty($lesson)) {
            throw $this->createNotFoundException('lesson not found');
        }

        $this->beginTransaction();
        try {
            $affected = $this->getCourseLessonDao()->update($id, array('status' => 'teaching', 'startTime' => time()));
            $course = $this->getCourseService()->getCourse($lesson['courseId']);

            $data = array(
                'lessonId' => $lesson['id'],
                'courseId' => $course['id'],
            );

            $user = $this->getCurrentUser();
            $this->getLogService()->info('courseLesson', 'start_course_lesson', "{$user['truename']}开始课程《{$course['title']}》", $data);
            $this->dispatchEvent('course.lesson.start', new Event($affected, array(
                'userId' => $user['id']
            )));

            $this->commit();

            return $affected;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function findTeachingLessons()
    {
        return $this->getCourseLessonDao()->search(array('status' => 'teaching'), array(), 0, PHP_INT_MAX);
    }

    public function findAllLessons()
    {
        return $this->getCourseLessonDao()->findAllLessons();
    }

    public function endLessons($lessonIds)
    {
        foreach ($lessonIds as $lessonId) {
            $this->endCourseLesson($lessonId);
        }
    }

    public function endCourseLesson($id)
    {
        $lesson = $this->getCourseLesson($id);
        $this->dispatchEvent('push.lesson.end', new Event(array('id' => $id, 'courseId' => $lesson['courseId'])));

        if ($this->isOpenWorker()) {
            BeanstalkClient::putTubeMessage('LessonEndWorker', array('lessonId' => $id));
        } else {
            return $this->endCourseLessonProcess($id);
        }
    }

    public function endCourseLessonProcess($id)
    {
        $lesson = $this->getCourseLesson($id);
        if (empty($lesson)) {
            throw $this->createNotFoundException('lesson not found');
        }

        $lessonTasks = $this->findInLessonTasksByLessonId($lesson['id']);
        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');
        $memberCount = $this->getCourseMemberService()->getCourseStudentCount($lesson['courseId']);

        $this->beginTransaction();
        try {
            $affected = $this->getCourseLessonDao()->update($id, array('status' => 'teached', 'endTime' => time(), 'memberCount' => $memberCount));

            $this->getQuestionnaireService()->deleteDoingQuestionnaireByTaskIds($taskIds);
            $this->getTaskStatusService()->endTaskByTaskIds($taskIds);

            $signIns = $this->getSignInService()->findSignInsByLessonId($id);
            foreach ($signIns as $key => $signIn) {
                $this->getSignInService()->endSignIn($signIn['id']);
            }

            $this->getSignInService()->updateKeepAttendAndAbsentTimesByLessonId($id);

            $this->dispatchEvent('course.lesson.end', new Event($affected));
            $this->commit();

            return $affected;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function cancelCourseLesson($id)
    {
        if ($this->isOpenWorker()) {
            BeanstalkClient::putTubeMessage('LessonCancelWorker', array('lessonId' => $id));
        } else {
            return $this->cancelCourseLessonProcess($id);
        }
    }

    public function cancelCourseLessonProcess($id)
    {
        $lesson = $this->getCourseLesson($id);

        if (empty($lesson)) {
            throw $this->createNotFoundException('lesson not found');
        }
        $this->beginTransaction();
        try {
        $created = $this->getCourseLessonDao()->update($id, array('status' => 'created', 'startTime' => 0));
        $this->dispatchEvent('course.lesson.cancel', new Event($created));

        $this->commit();

        return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function findTeachingLessonsByCourseId($courseId)
    {
        return $this->getCourseLessonDao()->findByCourseIdAndStatus($courseId, 'teaching');
    }

    public function countLessonByCourseIds($courseIds)
    {
        return $this->getCourseLessonDao()->count(array('courseIds' => $courseIds));
    }

    public function countStatisticsLessonTaskByCourseIds($courseIds)
    {
        return $this->getTaskDao()->countStatisticsByCourseIds($courseIds);
    }

    public function countLessonByCourseIdsAndStatus($courseIds, $status)
    {
        return $this->getCourseLessonDao()->count(array(
            'courseIds' => $courseIds,
            'status' => $status
        ));
    }

    public function findCountLessonByCourseIds($courseIds)
    {
        return ArrayToolkit::index($this->getCourseLessonDao()->findCountLessonByCourseIds($courseIds), 'courseId');
    }

    public function findCountLessonByCourseIdsAndStatus($courseIds, $status)
    {
        return ArrayToolkit::index($this->getCourseLessonDao()->findCountLessonByCourseIdsAndStatus($courseIds, $status), 'courseId');
    }

    public function findLastTeachCourseLessonsByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        $lessons = array();
        foreach ($courseIds as $key => $courseId) {
            $lesson = $this->getCourseLessonDao()->getLastTeachedCourseLesson($courseId);
            if ($lesson) {
                $lessons[$courseId] = $lesson;
            }
        }

        return $lessons;
    }

    public function getTeachingCourseLessonByCourseId($courseId)
    {
        return $this->getCourseLessonDao()->getTeachingByCourseId($courseId);
    }

    public function getCourseLessonStatus($courseId)
    {
        $lesson = $this->getCourseLessonDao()->getTeachingByCourseId($courseId);

        return empty($lesson) ? false : true;
    }

    public function getCourseLessonByCourseIdAndNumber($courseId, $number)
    {
        return $this->getCourseLessonDao()->getByCourseIdAndNumber($courseId, $number);
    }

    public function findNextTeachCourseLessonsByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        $lessons = array();
        foreach ($courseIds as $key => $courseId) {
            $lastLesson = $this->getCourseLessonDao()->getLastTeachedCourseLesson($courseId);
            $number = 0;
            if ($lastLesson) {
                $number = $lastLesson['number'];
            }
            $lesson = $this->getCourseLessonDao()->getNextTeachCourseLesson($courseId, $number);
            if ($lesson) {
                $lessons[$courseId] = $lesson;
            }
        }

        return $lessons;
    }

    public function findCourseLessonsByIds($ids)
    {
        return ArrayToolkit::index($this->getCourseLessonDao()->findByIds($ids), 'id');
    }

    public function countLessonUnfinishedTask($conditions)
    {
        $user = $this->getCurrentUser();

        $tasks = $this->getTaskDao()->findTasksByLessonIdAndStage($conditions['lessonId'], $conditions['stage']);
        $taskCount = $this->countLessonTask($conditions);

        $taskIds = ArrayToolkit::column($tasks, 'taskId');

        if (empty($taskIds)) {
            $finishCount = 0;
        } else {
            $finishCount = $this->getTaskResultDao()->count(array(
                'userId' => $user['id'],
                'courseTaskIds' => $taskIds,
                'status' => 'finish',
            ));
        }

        $unfinishCount = $taskCount - $finishCount;

        return $unfinishCount;
    }

    public function findCourseLessonItems($lessonId)
    {
        $lessonTasks = $this->findLessonTasksByLessonId($lessonId);

        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');
        $tasks = $this->findTasksByTaskIds($taskIds);

        return $this->prepareCourseItems($lessonId, $tasks, 0);
    }

    protected function findTasksByTaskIds($taskIds)
    {
        $user = $this->getCurrentUser();
        if ($user->isLogin()) {
            return $this->getTaskService()->findTasksFetchActivityAndResultsByTaskIds($taskIds);
        }

        return $this->getTaskService()->findTasksFetchActivityByTaskIds($taskIds);
    }

    public function prepareCourseItems($lessonId, $tasks)
    {
        $tasks = ArrayToolkit::index($tasks, 'categoryId');

        $items = array(
            'before' => array(),
            'in' => array(),
            'after' => array(),
        );

        $chapters = $this->getCourseChapterDao()->findChaptersByLessonId($lessonId);
        $chapters = ArrayToolkit::group($chapters, 'stage');

        foreach ($chapters as $stage => $chapter) {
            $items[$stage] = $this->sortChapterTasks($chapter, $tasks);
        }

        return $items;
    }

    private function sortChapterTasks($chapters, $tasks)
    {
        $items = array();
        foreach ($chapters as $chapter) {
            $chapter['itemType'] = 'chapter';
            $items["chapter-{$chapter['id']}"] = $chapter;
        }

        $taskCount = 1;

        foreach ($items as $key => $item) {
            if ($item['type'] != 'lesson') {
                continue;
            }

            if (!empty($tasks[$item['id']])) {
                $items[$key]['task'] = $tasks[$item['id']];
                $taskCount += count($tasks[$item['id']]);
            } else {
                unset($items[$key]);
            }
        }

        return $items;
    }

    public function countCourseLessonByCourseId($conditions)
    {
        return $this->getCourseLessonDao()->count($conditions);
    }

    public function findCourseLessonCountByCourseIds($courseIds)
    {
        return ArrayToolkit::index($this->getCourseLessonDao()->findCourseLessonCountByCourseIds($courseIds), 'courseId');
    }

    public function createChapter($chapter)
    {
        if (!ArrayToolkit::requireds($chapter, array('lessonId', 'title'))) {
            throw $this->createInvalidArgumentException('lack of required fields');
        }

        $chapter = ArrayToolkit::parts($chapter, array(
            'lessonId',
            'title',
            'categoryId',
        ));

        $lesson = $this->getCourseLesson($chapter['lessonId']);
        $chapter['courseId'] = $lesson['courseId'];
        $chapter['number'] = 1;

        return $this->getChapterDao()->create($chapter);
    }

    public function deleteChapter($chapterId)
    {
        $chapter = $this->getChapter($chapterId);

        if (empty($chapter)) {
            throw $this->createNotFoundException('chapter not found');
        }

        $this->getChapterDao()->delete($chapterId);
        $this->dispatchEvent('czie.chapter.delete', new Event($chapter));

        $this->resetLessonTasksChapter($chapterId);
    }

    public function updateChapter($chapterId, $fields)
    {
        $chapter = $this->getChapter($chapterId);

        if (empty($chapter)) {
            throw $this->createNotFoundException('chapter not found');
        }

        $fields = ArrayToolkit::parts($fields, array('title', 'categoryId'));

        $affected = $this->getChapterDao()->update($chapterId, $fields);
        $this->getCourseService()->updateChapter($affected['courseId'], $affected['categoryId'], array('title' => $affected['title']));

        return $affected;
    }

    public function getChapter($chapterId)
    {
        return $this->getChapterDao()->get($chapterId);
    }

    public function findChaptersByLessonId($lessonId)
    {
        return ArrayToolkit::index($this->getChapterDao()->findByLessonId($lessonId), 'id');
    }

    public function getCourseChapter($courseChapterId)
    {
        return $this->getCourseChapterDao()->get($courseChapterId);
    }

    public function findChaptersByLessonIdAndTpye($lessonId, $type)
    {
        return $this->getCourseChapterDao()->findByLessonIdAndTpye($lessonId, $type);
    }

    public function findChapterByLessonIdAndStage($lessonId, $stage)
    {
        return $this->getCourseChapterDao()->findChapterByLessonIdAndStage($lessonId, $stage);
    }

    public function findCourseChaptersByLessonId($lessonId)
    {
        return $this->getCourseChapterDao()->findChaptersByLessonId($lessonId);
    }

    public function createLessonTask($lessonTask)
    {
        if (!ArrayToolkit::requireds($lessonTask, array('courseId', 'lessonId', 'taskId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $lessonTask = ArrayToolkit::parts($lessonTask, array(
            'courseId',
            'lessonId',
            'taskId',
            'stage',
            'chapterId',
            'aimIds',
        ));

        list(
            $task,
            $notInFields,
        ) = $this->fetchNotInFields($lessonTask);

        $task['chapterId'] = empty($task['chapterId']) ? 0 : $task['chapterId'];

        $count = $this->countLessonTask(array(
            'stage' => $task['stage'],
            'lessonId' => $task['lessonId'],
        ));
        $task['seq'] = $count + 1;

        $this->beginTransaction();
        try {
            $created = $this->getTaskDao()->create($task);

            $created['isCopy'] = isset($lessonTask['copy']);
            $this->dispatchEvent('lesson.task.create', new Event($created, array(
                'fields' => $notInFields
            )));

            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function fetchNotInFields($task)
    {
        $notInFields = array();

        if (isset($task['aimIds'])) {
            $notInFields['aimIds'] = $task['aimIds'];
            unset($task['aimIds']);
        }

        return array(
            $task,
            $notInFields,
        );
    }

    public function deleteLessonTask($lessonTaskId)
    {
        $lessonTask = $this->getLessonTask($lessonTaskId);
        $lesson = $this->getCourseLesson($lessonTask['lessonId']);
        if (!empty($lesson)) {
             $this->taskNumByLessonId($lesson['id'], --$lesson['taskNum']);
         }

        return $this->getTaskDao()->delete($lessonTaskId);
    }

    public function resetLessonTasksChapter($chapterId)
    {
        return $this->getTaskDao()->resetLessonTasksChapter($chapterId);
    }

    public function updateLessonTask($taskId, $fields)
    {
        $task = $this->getLessonTask($taskId);

        if (empty($task)) {
            throw $this->createNotFoundException('task not found');
        }

        $fields = ArrayToolkit::parts($task, array('chapterId'));

        return $this->getTaskDao()->update($taskId, $fields);
    }

    public function countClassReportsByOrgCodeAndTimeRange($orgCode, $startTime, $endTime)
    {
        return $this->getCourseLessonDao()->countByOrgCodeAndTimeRange($orgCode, $startTime, $endTime);
    }

    public function getLessonTask($id)
    {
        return $this->getTaskDao()->get($id);
    }

    public function getLessonTaskByTaskId($taskId)
    {
        return $this->getTaskDao()->getByTaskId($taskId);
    }

    public function countLessonTask($conditions)
    {
        return $this->getTaskDao()->count($conditions);
    }

    public function countLessonTasksByLessonIds($lessonIds)
    {
        return ArrayToolkit::index($this->getTaskDao()->countByLessonIds($lessonIds), 'lessonId');
    }

    public function findLessonTasksByLessonId($lessonId)
    {
        return $this->getTaskDao()->findByLessonId($lessonId);
    }

    public function findLessonTasksByLessonIdAndStage($lessonId, $stage)
    {
        return $this->getTaskDao()->findTasksByLessonIdAndStage($lessonId, $stage);
    }

    public function findInLessonTasksByLessonId($lessonId)
    {
        return $this->getTaskDao()->findInTasksByLessonId($lessonId);
    }

    public function tryManageCourseLesson($lessonId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        $lesson = $this->getCourseLessonDao()->get($lessonId);

        if (empty($lesson)) {
            throw $this->createNotFoundException("lesson#{$lessonId} Not Found");
        }
        $course = $this->getCourseService()->getCourse($lesson['courseId']);

        return $lesson;
    }

    protected function createCourseStrategy($course)
    {
        return StrategyContext::getInstance()->createStrategy($course['isDefault'], $this->biz);
    }

    public function getCurrenTeachCourseLesson($courseId)
    {
        return $this->getCourseLessonDao()->getCurrenTeachCourseLesson($courseId);
    }

    protected function getChapterDao()
    {
        return $this->createDao('CustomBundle:Course:ChapterDao');
    }

    public function getCourseLesson($id)
    {
        return $this->getCourseLessonDao()->get($id);
    }

    public function searchCourseLesson($conditions, $sort, $start, $limit)
    {
        return $this->getCourseLessonDao()->search($conditions, $sort, $start, $limit);
    }

    public function findCourseLessonsByCourseId($courseId)
    {
        return ArrayToolkit::index($this->getCourseLessonDao()->findByCourseId($courseId), 'id');
    }

    public function findCourseLessonsByCourseIdAndStatus($courseId, $status)
    {
        return ArrayToolkit::index($this->getCourseLessonDao()->findByCourseIdAndStatus($courseId, $status), 'id');
    }

    public function findCourseLessonsByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        return $this->getCourseLessonDao()->findByCourseIds($courseIds);
    }

    public function getStudyLessonByCourseId($courseId)
    {
        $currenLesson = $this->getTeachingCourseLessonByCourseId($courseId);
        if (empty($currenLesson)) {
            $lessons = $this->getCourseLessonDao()->findByCourseId($courseId);
            $lessons = array_reverse($lessons);
            foreach ($lessons as $lesson) {
                if ($lesson['status'] == 'teached') {
                    break;
                }
                $currenLesson = $lesson;
            }
            $currenLesson = empty($currenLesson) ? reset($lessons) : $currenLesson;
        }

        return $currenLesson;
    }

    public function findLessonsByFromCourseIdAndtoCourseId($fromCourseId, $toCourseId)
    {
        $fromLessons = $this->findCourseLessonsByCourseId($fromCourseId);
        $fromLessons = ArrayToolkit::index($fromLessons, 'number');
        $toLessons = $this->findCourseLessonsByCourseId($toCourseId);
        $toLessons = ArrayToolkit::index($toLessons, 'number');

        $lessonCount = 0;
        $result = array();
        foreach ($toLessons as $key => $toLesson) {
            $toChapters = $this->findCourseChaptersByLessonId($toLesson['id']);
            if (!empty($toChapters)) {
                $lessonCount++;
                continue;
            }

            if (!isset($fromLessons[$key])) {
                continue;
            }

            $fromChapters = $this->findCourseChaptersByLessonId($fromLessons[$key]['id']);
            if (empty($fromChapters)) {
                continue;
            }

            $result[] = $toLesson;
        }

        if ($lessonCount >= count($toLessons)) {
            throw $this->createAccessDeniedException('课程教案已存在，不能导入');
        }

        if (empty($result)) {
            throw $this->createAccessDeniedException('没有可以导入的课次内容');
        }

        return $result;
    }

    public function findCompleteLessonByCourseId($courseId)
    {
        $user = $this->getCurrentUser();
        $lessons = $this->findCourseLessonsByCourseId($courseId);
        $tasks = $this->getTaskService()->findTasksByCourseId($courseId);
        $chapters = $this->getCourseChapterDao()->findChaptersByCourseId($courseId);
        $evaluations = $this->getLessonEvaluationService()->findEvaluationsByCourseIdAndUserId($courseId, $user['id']);
        $taskResults = $this->getTaskResultDao()->findByCourseIdAndUserId($courseId, $user['id']);

        $teachers = $this->getCourseMemberService()->findCourseTeachers($courseId);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');
        $teacherTaskResults = $this->getTaskResultDao()->findByCourseIdAndUserIds($courseId, $teacherIds);
        $evaluations = ArrayToolkit::index($evaluations, 'lessonId');
        $chapters = ArrayToolkit::group($chapters, 'lessonId');
        $isCourseTeacher = $this->getCourseMemberService()->isCourseTeacher($courseId, $user['id']);

        $result = array();
        foreach ($lessons as $lessonId => $lesson) {
            $cpLesson = array(
                'isEvaluation' => isset($evaluations[$lesson['id']]),
                'isShowPhase' => $lesson['status'] != 'teached',
                'number' => $lesson['number'],
                'status' => $lesson['status'],
                'title' => empty($lesson['title']) ? '课次'.$lesson['number'] : $lesson['title'],
                'id' => $lesson['id'],
            );
            if (empty($chapters[$lessonId])) {
                continue ;
            }
            $data = array(
                'lesson' => $lesson,
                'chapters' => $chapters[$lessonId],
                'tasks' => $tasks,
                'taskResults' => $taskResults,
                'teacherTaskResults' => $teacherTaskResults,
                'isCourseTeacher' => $isCourseTeacher,
            );

            $result[] = array_merge($cpLesson, $this->lessonTaskSort($data));
        }

        return $result;
    }

    public function lessonTaskSort($data)
    {
        $chapters = ArrayToolkit::group($data['chapters'], 'stage');
        $startedTaskIds = ArrayToolkit::column($data['teacherTaskResults'], 'courseTaskId');
        $taskResults = ArrayToolkit::index($data['taskResults'], 'courseTaskId');
        $tasks = ArrayToolkit::index($data['tasks'], 'categoryId');
        $result = array(
            'before' => array(),
            'in' => array(),
            'after' => array(),
        );

        foreach ($chapters as $stage => $chapterType) {
            foreach ($chapterType as $chapter) {
                $task = array(
                    'courseId' => $chapter['courseId'],
                    'lessonId' => $chapter['lessonId'],
                    'taskType' => $chapter['type'],
                    'title' => $chapter['title'],
                );
                if ($chapter['type'] != 'chapter') {
                    if (empty($tasks[$chapter['id']]['id'])) {
                        continue ;
                    }
                    $taskId = $tasks[$chapter['id']]['id'];
                    $task['id'] = $tasks[$chapter['id']]['activityId'];
                    $task['isVisible'] = false;
                    $task['status'] = isset($taskResults[$taskId]) ? $taskResults[$taskId]['status'] == 'finish' : false;
                    $task['taskId'] = $taskId;
                    $task['taskType'] = $chapter['type'];
                    $task['activityType'] = $tasks[$chapter['id']]['type'];
                    if ($data['isCourseTeacher'] || in_array($taskId, $startedTaskIds) || $data['lesson']['status'] == 'teached' || $stage == 'before') {
                        $task['isVisible'] = true;
                    }

                    $task = $this->changeTimeType($task, $tasks[$chapter['id']]['length']);
                }
                $result[$stage][$chapter['seq']] = $task;
            }
        }

        return $result;
    }

    public function findLessonTaskStagesByLessonId($lessonId)
    {
        $user = $this->getCurrentUser();
        $lesson = $this->tryManageCourseLesson($lessonId);
        $lessonTasks = $this->findLessonTasksByLessonId($lessonId);

        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');
        $tasks = $this->getTaskService()->findTasksByIds($taskIds);
        $taskResults = $this->getTaskResultDao()->findByTaskIdsAndUserId($taskIds, $user['id']);
        $teachers = $this->getCourseMemberService()->findCourseTeachers($lesson['courseId']);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');
        $teacherTaskResults = $this->getTaskService()->findResultsByTaskIdsAndUserIds($taskIds, $teacherIds);

        $evaluations = $this->getLessonEvaluationService()->findEvaluationsByLessonId($lessonId);
        $chapters = $this->findCourseChaptersByLessonId($lessonId);
        $isCourseTeacher = $this->getCourseMemberService()->isCourseTeacher($lesson['courseId'], $user['id']);

        $cpLesson = array(
            'isEvaluation' => isset($evaluations[$lesson['id']]),
            'isShowPhase' => $lesson['status'] != 'teached',
            'number' => $lesson['number'],
            'status' => $lesson['status'],
            'title' => empty($lesson['title']) ? '课次'.$lesson['number'] : $lesson['title'],
            'id' => $lesson['id'],
        );
        $data = array(
            'lesson' => $lesson,
            'chapters' => $chapters,
            'tasks' => $tasks,
            'taskResults' => $taskResults,
            'teacherTaskResults' => $teacherTaskResults,
            'isCourseTeacher' => $isCourseTeacher,
        );

        return $this->lessonTaskSort($data);
    }

    protected function changeTimeType($task, $length)
    {
        if ($task['activityType'] == 'video' || $task['activityType'] == 'audio') {
            if ($length > 3600) {
                $hours = intval($length/3600);
                $minutes = $length % 3600;
                $task['length'] = $hours.":".gmstrftime('%M:%S', $minutes);
            } else {
                $task['length'] = gmstrftime('%M:%S', $length);
            }
        }


        return $task;
    }

    public function countStudentsFinishOutTasksByCourseId($courseId)
    {
        return $this->getTaskResultDao()->countStudentsTasksByCourseIdAndStatus($courseId, 'finish');
    }

    public function findLessonTasksByCourseId($courseId)
    {
        return $this->getTaskDao()->findByCourseId($courseId);
    }

    public function findTeachedLessonTasksByCourseId($courseId)
    {
        return $this->getTaskDao()->findTeachedLessonTasksByCourseId($courseId);
    }

    public function findInLessonTasksByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }
        return $this->getTaskDao()->findInByTaskIds($taskIds);
    }

    public function findLessonTasksByTaskIds($taskIds)
    {
        return $this->getTaskDao()->findByTaskIds($taskIds);
    }

    public function countSchoolTeachersPrepareLessonsByTermCode($termCode)
    {
        return count($this->getTaskDao()->findSchoolTasksByTermCode($termCode));
    }

    public function countScholeTeacherReportsByTermCode($termCode)
    {
        return $this->getCourseLessonDao()->countSchoolTasksByTermCode($termCode);
    }

    public function findTeachedCourseLessonByTime($startTime, $endTime)
    {
        return $this->getCourseLessonDao()->findTeachedByTime($startTime, $endTime);
    }

    public function findLessonTasksByLessonIds($lessonIds)
    {
        if (empty($lessonId)) {
            return array();
        }

        return $this->getTaskDao()->findLessonTasksByLessonIds($lessonIds);
    }

    public function findOutLessonTasksByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }

        return $this->getTaskDao()->findOutLessonTasksByTaskIds($taskIds);
    }

    protected function getCourseLessonDao()
    {
        return $this->createDao('CustomBundle:Course:CourseLessonDao');
    }

    protected function getCourseChapterDao()
    {
        return $this->createDao('CustomBundle:Course:CourseChapterDao');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getTaskDao()
    {
        return $this->createDao('CustomBundle:Course:LessonTaskDao');
    }

    protected function getTaskResultDao()
    {
        return $this->createDao('CustomBundle:Task:TaskResultDao');
    }

    protected function getStartRecordService()
    {
        return $this->createService('CustomBundle:Course:StartRecordService');
    }

    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    protected function getQuestionnaireService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    public function getLessonEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function isOpenWorker()
    {
        $magic = $this->createService('System:SettingService')->get('magic');

        if (isset($magic['open_worker']) && $magic['open_worker']) {
            return true;
        }

        return false;
    }

    public function countCourseLesson($conditions)
    {
        return $this->getCourseLessonDao()->count($conditions);
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }
}
