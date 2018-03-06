<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\BrainStormResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class BrainStormResultDaoImpl extends GeneralDaoImpl implements BrainStormResultDao
{
    protected $table = 'activity_brain_storm_result';

    public function findByTaskId($taskId, $count)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `courseTaskId` = ? ORDER BY `id` ASC LIMIT {$count}";

        return $this->db()->fetchAll($sql, array($taskId)) ?: array();
    }

    public function countByTaskId($taskId)
    {
        $sql = "SELECT sum(memberCount) FROM {$this->table} WHERE `courseTaskId` = ?";

        return $this->db()->fetchColumn($sql, array($taskId)) ?: 0;
    }

    public function findByTaskIds($taskIds)
    {
        return $this->findInField('courseTaskId', $taskIds);
    }

    public function getByTaskIdAndGroupId($taskId, $groupId)
    {
        return $this->getByFields(array(
            'courseTaskId' => $taskId,
            'groupId' => $groupId,
        ));
    }

    public function findByTaskIdAndGroupId($taskId, $groupId)
    {
        return $this->findByFields(array(
            'courseTaskId' => $taskId,
            'groupId' => $groupId,
        ));
    }


    public function getByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getByFields(array(
            'courseTaskId' => $taskId,
            'userId' => $userId,
        ));
    }

    public function declares()
    {
        return array(
            'serializes' => array('remark' => 'delimiter'),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
