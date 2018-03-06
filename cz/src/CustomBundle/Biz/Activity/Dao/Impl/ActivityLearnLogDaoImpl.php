<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use Biz\Activity\Dao\Impl\ActivityLearnLogDaoImpl as BaseDaoImpl;

class ActivityLearnLogDaoImpl extends BaseDaoImpl
{
    public function deleteByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE courseTaskId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $taskIds);
    }
}
