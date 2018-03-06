<?php

namespace CustomBundle\Biz\Practice\Dao\Impl;

use CustomBundle\Biz\Practice\Dao\ResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ResultDaoImpl extends GeneralDaoImpl implements ResultDao
{
    protected $table = 'activity_practice_result';

    public function findByTaskId($taskId)
    {
        return $this->findByFields(array(
            'courseTaskId' => $taskId
        ));
    }

    public function getByUserIdAndTaskId($userId, $taskId)
    {
        return $this->getByFields(array(
            'userId' => $userId,
            'courseTaskId' => $taskId
        ));
    }

    public function findByActivityId($activityId, $count)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `activityId` = ? AND isTeacher = '0' ORDER BY `createdTime` DESC LIMIT {$count}";

        return $this->db()->fetchAll($sql, array($activityId)) ?: array();
    }

    public function findByActivityIdAndIsTeacher($activityId, $isTeacher)
    {
        return $this->findByFields(array(
            'activityId' => $activityId,
            'isTeacher' => $isTeacher,
        ));
    }

    public function findByTaskIds($taskIds)
    {
        return $this->findInField('courseTaskId', $taskIds);
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
