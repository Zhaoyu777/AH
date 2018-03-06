<?php

namespace CustomBundle\Biz\Activity\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Activity\Service\BrainStormResultService;

class BrainStormResultServiceImpl extends BaseService implements BrainStormResultService
{
    public function createResult($result)
    {
        if (!ArrayToolkit::requireds($result, array('activityId', 'courseId', 'courseTaskId', 'userId', 'groupId', ))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        // if (!empty($this->getResultByTaskIdAndUserId($result['courseTaskId'], $result['userId']))) {
        //     throw $this->createAccessDeniedException('你已提交结果，请勿重复提交。');
        // }

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if ($lesson['status'] != 'teaching') {
            throw $this->createAccessDeniedException('课次未开始或已下课，不能提交结果。');
        }

        $status = $this->getStatusService()->getStatusByTaskId($result['courseTaskId']);
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

        /**
         * @todo 这块跟产品确认后，修改成异常还是更新／
         */
        $orign = $this->getResultByTaskIdAndUserId($result['courseTaskId'], $result['userId']);
        if (!empty($orign)) {
            return $this->changeResult($orign['id'], $result);
        }

        $activity = $this->getActivityService()->getActivity($result['activityId']);
        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $activity = $config->get($activity['mediaId']);
        $result['memberCount'] = 1;
        if ($activity['submitWay'] == 'group') {
            $result['memberCount'] = $this->getTaskGroupService()->countTaskGroupMembersByGroupId($result['groupId']);
        }

        $brainStorm = $this->getResultDao()->create($result);

        $this->dispatchEvent('brain_storm.create', new Event($brainStorm));

        return $brainStorm;
    }

    public function deleteResult($resultId)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            return ;
        }

        $this->getResultDao()->delete($resultId);

        $this->dispatchEvent('brain_storm.result.delete', new Event($result));
    }

    public function deleteResultsByTaskIds($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);

        foreach ($results as $key => $result) {
            $this->deleteResult($result['id']);
        }
    }

    public function findResultsByTaskId($taskId, $count = PHP_INT_MAX)
    {
        return $this->getResultDao()->findByTaskId($taskId, $count);
    }

    public function getResultByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getResultDao()->getByTaskIdAndUserId($taskId, $userId);
    }

    /**
     * 该方法只用于分组按组提交情况调用
     */
    public function getResultByTaskIdAndGroupId($taskId, $groupId)
    {
        return $this->getResultDao()->getByTaskIdAndGroupId($taskId, $groupId);
    }

    public function findResultsByTaskIdAndGroupId($taskId, $groupId)
    {
        return $this->getResultDao()->findByTaskIdAndGroupId($taskId, $groupId);
    }

    public function changeResult($resultId, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array(
            'userId',
            'content'
        ));
        $brainStorm = $this->getResultDao()->update($resultId, $fields);

        $this->dispatchEvent('brain_storm.update', new Event($brainStorm));

        return $brainStorm;
    }

    public function getResult($resultId)
    {
        return $this->getResultDao()->get($resultId);
    }

    public function remark($resultId, $fields)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            throw $this->createNotFoundException('该结果不存在。');
        }

        $fields = ArrayToolkit::parts($fields, array('remark', 'score'));
        $user = $this->getCurrentUser();
        $fields['opUserId'] = $user['id'];
        if (!empty($fields['remark'])) {
            $fields['remark'] = array_filter($fields['remark']);
        }

        $result = $this->getResultDao()->update($resultId, $fields);
        $this->dispatchEvent('brain.storm.remark', new Event($result));
        $this->dispatchEvent('task.result.remark', new Event($result));

        return $result;
    }

    public function groupRemark($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('id', 'remark', 'score'))) {
            return ;
        }

        $result = array();
        $remarks = array_chunk($fields['remark'], 5);

        foreach ($fields['id'] as $key => $field) {
            if ($fields['score'][$key] == 0) {
                continue ;
            }
            $remark['score'] = $fields['score'][$key];
            $remark['remark'] = $remarks[$key];
            $result[] = $this->remark($field, $remark);
        }

        return $result;
    }

    public function countResultByTaskId($taskId)
    {
        return $this->getResultDao()->countByTaskId($taskId);
    }

    public function findGroupResultsByTaskIdAndGroupId($taskId, $groupId)
    {
        return $this->getResultDao()->findByTaskIdAndGroupId($taskId, $groupId);
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:Activity:BrainStormResultDao');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }
}
