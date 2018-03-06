<?php

namespace CustomBundle\Biz\RandomGroup\Dao\Impl;

use CustomBundle\Biz\RandomGroup\Dao\RandomGroupDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RandomGroupDaoImpl extends GeneralDaoImpl implements RandomGroupDao
{
    protected $table = 'czie_random_group_member';

    public function getByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getByFields(array(
            'taskId' => $taskId,
            'userId' => $userId,
        ));
    }

    public function findByTaskId($taskId)
    {
        return $this->findByFields(array('taskId' => $taskId));
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
