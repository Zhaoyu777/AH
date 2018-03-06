<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\RollcallResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RollcallResultDaoImpl extends GeneralDaoImpl implements RollcallResultDao
{
    protected $table = 'activity_rollcall_result';

    public function getByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getByFields(array('courseTaskId' => $taskId, 'userId' => $userId));
    }

    public function findByTaskIds($taskIds)
    {
        return $this->findInField('courseTaskId', $taskIds);
    }

    public function findByTaskId($taskId)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE `courseTaskId` = ? ORDER BY `id` DESC ";

        return $this->db()->fetchAll($sql, array($taskId));
    }

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function declares()
    {
        return array(
            'serializes' => array('remark' => 'delimiter'),
            'orderbys' => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(),
        );
    }
}
