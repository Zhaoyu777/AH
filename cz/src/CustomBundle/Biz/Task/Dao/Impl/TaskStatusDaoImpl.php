<?php

namespace CustomBundle\Biz\Task\Dao\Impl;

use CustomBundle\Biz\Task\Dao\TaskStatusDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class TaskStatusDaoImpl extends GeneralDaoImpl implements TaskStatusDao
{
    protected $table = 'czie_task_status';

    public function getByTaskId($taskId)
    {
        return $this->getByFields(array('courseTaskId' => $taskId));
    }

    public function getByActivityId($activityId)
    {
        return $this->getByFields(array('activityId' => $activityId));
    }

    public function findByTaskIds($taskIds)
    {
        return $this->findInField('courseTaskId', $taskIds);
    }

    public function endByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "UPDATE {$this->table} SET `status` = 'end' WHERE courseTaskId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $taskIds);
    }

    public function deleteByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE courseTaskId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $taskIds);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
