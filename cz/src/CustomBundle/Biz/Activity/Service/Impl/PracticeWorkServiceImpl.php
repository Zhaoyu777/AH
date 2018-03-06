<?php

namespace CustomBundle\Biz\Activity\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Activity\Service\PracticeWorkService;

class PracticeWorkServiceImpl extends BaseService implements PracticeWorkService
{
    public function createResult($result)
    {
        if (!ArrayToolkit::requireds($result, array('activityId', 'fileId', 'taskId', 'userId', 'practiceWorkId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $result = ArrayToolkit::parts($result, array(
            'activityId',
            'fileId',
            'practiceWorkId',
            'taskId',
            'userId',
            'origin',
            'finalSubTime',
        ));
        $this->beginTransaction();
        try {
            $created = $this->getResultDao()->create($result);
            $this->createScore($created);
            $this->getTaskService()->finishTaskResult($created['taskId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($created['taskId']);
            if ($lessonTask['stage'] == 'in') {
                $this->dispatchEvent('practice.work.create', new Event($created));
            }
            $this->commit();
            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    private function createScore($created)
    {
        $activity = $this->getActivityService()->getActivity($created['activityId']);
        if (!$activity['score'] > 0) {
            return ;
        }
        $course = $this->getCourseService()->getCourse($activity['fromCourseId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($created['taskId']);
        $courseLesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        $task = $this->getTaskService()->getTask($created['taskId']);

        $score = array(
            'courseId' => $activity['fromCourseId'],
            'taskId' => $created['taskId'],
            'lessonId' => $courseLesson['id'],
            'type' => 'operate',
            'term' => empty($course['termCode']) ? '' : $course['termCode'],
            'score' => $activity['score'],
            'targetType' => 'practiceWork',
            'targetId' => $created['id'],
            'userId' => $created['userId'],
            'remark' => '课次'.$courseLesson['number'].' - '.$task['title'],
        );

        $this->getScoreService()->createScore($score);
    }

    public function getResult($resultId)
    {
        return $this->getResultDao()->get($resultId);
    }

    public function updateResult($resultId, $fields)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            throw $this->createNotFoundException('学员未提交实践作业');
        }
        $this->beginTransaction();
        try {
            $updated = $this->getResultDao()->update($resultId, $fields);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($updated['taskId']);
            if ($lessonTask['stage'] == 'in') {
                $this->dispatchEvent('practice.work.update', new Event($updated));
            }
            $this->commit();
            return $updated;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deleteResultsByTaskIds($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);

        foreach ($results as $result) {
            $this->getResultDao()->delete($result['id']);
        }
    }

    public function reviewResult($resultId, $fields)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            throw $this->createNotFoundException('学员未提交实践作业');
        }

        return $this->getResultDao()->update($resultId, $fields);
    }

    public function getResultByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getResultDao()->getByTaskIdAndUserId($taskId, $userId);
    }

    public function findResultsStatusNumGroupByStatus($practiceWorkId)
    {
        $numInfo = $this->getResultDao()->findResultsStatusNumGroupByStatus($practiceWorkId);

        if (!$numInfo) {
            return array();
        }

        $statusInfo = array();
        foreach ($numInfo as $info) {
            $statusInfo[$info['status']] = $info['num'];
        }

        return $statusInfo;
    }

    public function findResultByPracticeWorkIds(array $practiceWorkIds)
    {
        return $this->getResultDao()->findResultByPracticeWorkIds($practiceWorkIds);
    }

    public function searchResults(array $conditions, array $orderBy, $start, $limit)
    {
        return $this->getResultDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function searchResultsCount(array $conditions)
    {
        return $this->getResultDao()->count($conditions);
    }

    public function getLastResultByPracticeWorkId($practiceWorkId)
    {
        return $this->getResultDao()->getLastResultByPracticeWorkId($practiceWorkId);
    }

    public function findPracticeWorkResultsByPracticeWorkId($practiceWorkId)
    {
        return $this->getResultDao()->findPracticeWorkResultsByPracticeWorkId($practiceWorkId);
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:Activity:PracticeWorkResultDao');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getScoreService()
    {
        return $this->createService('CustomBundle:Score:ScoreService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
