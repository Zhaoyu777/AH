<?php

namespace CustomBundle\Biz\Activity\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Activity\Service\OneSentenceResultService;

class OneSentenceResultServiceImpl extends BaseService implements OneSentenceResultService
{
    public function createResult($result)
    {
        if (!ArrayToolkit::requireds($result, array('activityId', 'courseId', 'courseTaskId', 'userId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        if (!empty($this->getResultByTaskIdAndUserId($result['courseTaskId'], $result['userId']))) {
            throw $this->createAccessDeniedException('你已提交结果，请勿重复提交。');
        }

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if ($lesson['status'] != 'teaching') {
            throw $this->createAccessDeniedException('课次未开始或已下课，不能提交结果。');
        }

        $status = $this->getStatusService()->getStatusByActivityId($result['activityId']);
        if (!(!empty($status) && $status['status'] == 'start')) {
            throw $this->createAccessDeniedException('活动未开始或已结束。');
        }

        $result = ArrayToolkit::parts($result, array(
            'activityId',
            'courseId',
            'courseTaskId',
            'userId',
            'groupId',
            'content',
        ));

        if (!empty($result['groupId'])) {
            $result['replyCount'] = $this->getGroupMemberService()->countGroupMembersByGroupId($result['groupId']);
        }

        $this->beginTransaction();
        try {
            $created = $this->getResultDao()->create($result);
            $this->dispatchEvent('one_sentence.result.create', $created);
            $taskResult = $this->getTaskService()->finishTaskResult($result['courseTaskId']);
            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deleteResult($resultId)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            return ;
        }

        $this->getResultDao()->delete($resultId);

        $this->dispatchEvent('one_sentence.result.delete', new Event($result));
    }

    public function deleteResultsByTaskIds($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);

        foreach ($results as $key => $result) {
            $this->deleteResult($result['id']);
        }
    }

    public function getResult($id)
    {
        return $this->getResultDao()->get($id);
    }

    public function getResultByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getResultDao()->getByTaskIdAndUserId($taskId, $userId);
    }

    public function findResultsByTaskId($taskId)
    {
        return $this->getResultDao()->findByTaskId($taskId);
    }

    public function countResultByTaskId($taskId)
    {
        return $this->getResultDao()->countByTaskId($taskId);
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:Activity:OneSentenceResultDao');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }
}
