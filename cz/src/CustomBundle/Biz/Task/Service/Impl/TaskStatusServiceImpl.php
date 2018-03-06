<?php

namespace CustomBundle\Biz\Task\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Task\Service\TaskStatusService;

class TaskStatusServiceImpl extends BaseService implements TaskStatusService
{
    public function startTask($taskId, $activityId)
    {
        $status = $this->getStatusByTaskId($taskId);

        if (!empty($status) && $status['status'] == 'start') {
            return $status;
        }

        if (empty($status)) {
            $fields = array(
                'courseTaskId' => $taskId,
                'activityId' => $activityId,
                'status' => 'start',
            );

            $status = $this->getTaskStatusDao()->create($fields);
        } else {
            $status = $this->getTaskStatusDao()->update($status['id'], array('status' => 'start'));
        }
        $this->dispatchEvent('push.task.start', new Event($status));

        return $status;
    }

    public function endTask($taskId, $batchDelete = false)
    {
        $status = $this->getTaskStatusDao()->getByTaskId($taskId);
        if (empty($status) && $batchDelete == false) {
            throw $this->createNotFoundException('该任务状态不存在');
        }

        if ($status['status'] == 'start') {
            $costTime = $status['costTime'] > 0 ? $status['costTime'] + time() - $status['updatedTime'] : time() - $status['createdTime'];
            $status = $this->getTaskStatusDao()->update($status['id'], array('status' => 'end', 'costTime' => $costTime));
            $this->dispatchEvent('push.task.end', new Event($status));
        }

        return $status;
    }

    public function endTaskByTaskIds($taskIds)
    {
        foreach ($taskIds as $taskId) {
            $this->endTask($taskId, true);
        }
    }

    public function deleteStatusByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return null;
        }

        return $this->getTaskStatusDao()->deleteByTaskIds($taskIds);
    }

    public function getStatusByTaskId($taskId)
    {
        $taskStatus = $this->getTaskStatusDao()->getByTaskId($taskId);
        if (empty($taskStatus)) {
            return null;
        }

        if ($taskStatus['status'] == 'start') {
            $taskStatus['costTime'] = $this->costTime($taskStatus);
        }

        return $taskStatus;
    }

    protected function costTime($taskStatus)
    {
        return $taskStatus['costTime'] > 0 ? $taskStatus['costTime'] + time() - $taskStatus['updatedTime'] : time() - $taskStatus['createdTime'];
    }

    public function findStatusesByTaskIds($taskIds)
    {
        return $this->getTaskStatusDao()->findByTaskIds($taskIds);
    }

    /**
     * 下面方法需要删除
     */
    public function getStatusByActivityId($activityId)
    {
        return $this->getTaskStatusDao()->getByActivityId($activityId);
    }

    protected function getTaskStatusDao()
    {
        return $this->createDao('CustomBundle:Task:TaskStatusDao');
    }
}
