<?php

namespace CustomBundle\Biz\RaceAnswer\Dao\Impl;

use CustomBundle\Biz\RaceAnswer\Dao\ResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ResultDaoImpl extends GeneralDaoImpl implements ResultDao
{
    protected $table = 'activity_race_answer_result';

    public function getByUserIdAndTaskId($userId, $taskId)
    {
        return $this->getByFields(array(
            'userId' => $userId,
            'courseTaskId' => $taskId,
        ));
    }

    public function findByUserIdsAndTaskId($userIds, $taskId)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `courseTaskId` = ? AND userId IN ({$marks}) ORDER BY `createdTime` DESC;";
        $fields = array_merge(array($taskId), $userIds);

        return $this->db()->fetchAll($sql, $fields);
    }

    public function findByTaskIds($taskIds)
    {
        return $this->findInField('courseTaskId', $taskIds);
    }

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function findByTaskId($taskId, $count)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `courseTaskId` = ? ORDER BY `id` ASC LIMIT {$count}";

        return $this->db()->fetchAll($sql, array($taskId)) ?: array();
    }

    public function declares()
    {
        return array(
            'serializes' => array('remark' => 'delimiter'),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'courseTaskId = :courseTaskId',
                'createdTime >= :raceCreatedTime',
                ),
        );
    }
}
