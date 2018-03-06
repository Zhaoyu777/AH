<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\OneSentenceResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class OneSentenceResultDaoImpl extends GeneralDaoImpl implements OneSentenceResultDao
{
    protected $table = 'activity_one_sentence_result';

    public function getByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getByFields(array('courseTaskId' => $taskId,'userId' => $userId));
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

    public function countByTaskId($taskId)
    {
        $sql = "SELECT count(id) FROM {$this->table} WHERE `courseTaskId` = ?";

        return $this->db()->fetchColumn($sql, array($taskId)) ?: 0;
    }

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
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
