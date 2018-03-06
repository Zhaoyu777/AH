<?php

namespace CustomBundle\Biz\TaskGroup\Dao\Impl;

use CustomBundle\Biz\TaskGroup\Dao\GroupDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class GroupDaoImpl extends GeneralDaoImpl implements GroupDao
{
    protected $table = 'czie_task_group';

    public function findByTaskId($taskId)
    {
        return $this->findByFields(array('taskId' => $taskId));
    }

    public function deleteByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE taskId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $taskIds);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array()
        );
    }
}
