<?php

namespace CustomBundle\Biz\DisplayWall\Dao\Impl;

use CustomBundle\Biz\DisplayWall\Dao\ResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ResultDaoImpl extends GeneralDaoImpl implements ResultDao
{
    protected $table = 'activity_display_wall_result';

    public function findByActivityId($activityId, $count)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `activityId` = ? ORDER BY `createdTime` DESC LIMIT {$count}";

        return $this->db()->fetchAll($sql, array($activityId)) ?: array();
    }

    public function findByTaskId($taskId)
    {
        return $this->findByFields(array('courseTaskId' => $taskId));
    }

    public function countByTaskId($taskId)
    {
        $sql = "SELECT sum(memberCount) FROM {$this->table} WHERE `courseTaskId` = ?";

        return $this->db()->fetchColumn($sql, array($taskId)) ?: 0;
    }

    public function getLastByActivityIdAndUserIds($activityId, $userIds)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `activityId` = ? AND userId IN ({$marks}) ORDER BY `createdTime` DESC LIMIT 1;";
        $fields = array_merge(array($activityId), $userIds);

        return $this->db()->fetchAssoc($sql, $fields);
    }

    public function getByUserIdAndTaskId($userId, $taskId)
    {
        return $this->getByFields(array(
            'userId' => $userId,
            'courseTaskId' => $taskId,
        ));
    }

    public function getByTaskIdAndGroupId($taskId, $groupId)
    {
        return $this->getByFields(array(
            'groupId' => $groupId,
            'courseTaskId' => $taskId,
        ));
    }

    public function findByActivityIdUserIds($activityId, $userIds)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `activityId` = ? AND userId IN ({$marks}) ORDER BY `createdTime` DESC;";
        $fields = array_merge(array($activityId), $userIds);

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function findByUserIdsAndTaskId($userIds, $taskId)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `courseTaskId` = ? AND userId IN ({$marks}) ORDER BY `createdTime` DESC;";
        $fields = array_merge(array($taskId), $userIds);

        return $this->db()->fetchAll($sql, $fields) ?: array();
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
