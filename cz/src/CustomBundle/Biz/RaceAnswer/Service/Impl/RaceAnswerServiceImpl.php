<?php

namespace CustomBundle\Biz\RaceAnswer\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\RaceAnswer\Service\RaceAnswerService;

class RaceAnswerServiceImpl extends BaseService implements RaceAnswerService
{
    public function tryRaceAnswer($result)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('未登陆');
        }

        $status = $this->getStatusService()->getStatusByTaskId($result['courseTaskId']);
        if (empty($status) || $status['status'] == 'end') {
            throw $this->createAccessDeniedException('活动未开始');
        }
    }

    public function createResult($result)
    {
        $this->tryRaceAnswer($result);

        $user = $this->getCurrentUser();
        if (!$this->getMemberService()->isCourseStudent($result['courseId'], $user['id'])) {
            throw $this->createAccessDeniedException("你不是该课程学生");
        }

        if (!ArrayToolkit::requireds($result, array('activityId', 'courseId', 'courseTaskId', 'userId'))) {
            throw $this->createInvalidArgumentException('缺少必要字段');
        }

        $countStudentNum = $this->countStudentNumByTaskId($result['courseTaskId']);
        if ($countStudentNum >= 10) {
            throw $this->createAccessDeniedException('未抢到');
        }

        $userResult = $this->getResultByUserIdAndTaskId($user['id'], $result['courseTaskId']);
        if (!empty($userResult)) {
            throw $this->createAccessDeniedException('你已经成功抢到');
        }

        $result = ArrayToolkit::parts($result, array(
            'activityId',
            'courseId',
            'courseTaskId',
            'userId',
        ));

        $created = $this->getResultDao()->create($result);

        $this->dispatchEvent('race.answer.create', new Event($created));

        return $created;
    }

    public function deleteResult($resultId)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            return ;
        }

        $this->getResultDao()->delete($resultId);

        $this->dispatchEvent('race.answer.delete', new Event($result));
    }

    public function deleteResultsByTaskIds($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);

        foreach ($results as $result) {
            $this->deleteResult($result['id']);
        }
    }

    public function remarkResult($id, $fields)
    {
        $user = $this->getCurrentUser();

        $result = $this->getResult($id);
        if (empty($result)) {
            throw $this->createInvalidArgumentException("缺少必要字段");
        }

        if (!$this->getMemberService()->isCourseTeacher($result['courseId'], $user['id'])) {
            throw $this->createAccessDeniedException("你不是该课程教师");
        }

        if ($result['score'] != 0) {
            throw $this->createAccessDeniedException("已评论");
        }

        $fields = ArrayToolkit::parts($fields, array('score', 'remark', 'createdTime'));

        if (!empty($fields['remark'])) {
            $fields['remark'] = array_filter($fields['remark']);
        }

        $user = $this->getCurrentUser();
        $fields['opUserId'] = $user['id'];

        $created = $this->getResultDao()->update($id, $fields);
        $this->dispatchEvent('race.answer.remark', new Event($created));
        $this->dispatchEvent('task.result.remark', new Event($created));

        return $created;
    }

    public function findResultsByUserIdsAndTaskId($userIds, $taskId)
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->getResultDao()->findByUserIdsAndTaskId($userIds, $taskId);
    }

    public function getResult($id)
    {
        return $this->getResultDao()->get($id);
    }

    public function findResultByTaskId($taskId, $count = PHP_INT_MAX)
    {
        return $this->getResultDao()->findByTaskId($taskId, $count);
    }

    public function getRankByTaskIdAndCreatedTime($taskId, $createdTime)
    {
        return $this->getResultDao()->count(array(
            'taskId' => $taskId,
            'raceCreatedTime' => $createdTime,
        ));
    }

    public function getResultByUserIdAndTaskId($userId, $taskId)
    {
        return $this->getResultDao()->getByUserIdAndTaskId($userId, $taskId);
    }

    public function countStudentNumByTaskId($taskId)
    {
        return $this->getResultDao()->count(array(
            'courseTaskId' => $taskId
        ));
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:RaceAnswer:ResultDao');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
