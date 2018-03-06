<?php

namespace CustomBundle\Biz\TaskGroup\Dao\Impl;

use CustomBundle\Biz\TaskGroup\Dao\MemberDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class MemberDaoImpl extends GeneralDaoImpl implements MemberDao
{
    protected $table = 'czie_task_group_member';

    public function getByUserIdAndTaskId($userId, $taskId)
    {
        return $this->getByFields(array(
            'userId' => $userId,
            'taskId' => $taskId,
        ));
    }

    public function getGroupCaptainByGroupId($groupId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `groupId` = ? ORDER BY `seq` ASC LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($groupId)) ?: null;
    }

    public function getCaptainByGroupId($groupId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `groupId` = ? ORDER BY `id` ASC LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($groupId)) ?: null;
    }

    public function deleteByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE taskId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $taskIds);
    }

    public function findByGroupId($groupId)
    {
        return $this->findByFields(array(
            'groupId' => $groupId,
        ));
    }

    public function findByTaskId($taskId)
    {
        return $this->findByFields(array('taskId' => $taskId));
    }

    public function countByTaskIdGroupByGroupId($taskId)
    {
        $sql = "SELECT groupId, COUNT(*) as count FROM {$this->table} WHERE `taskId` = ? GROUP BY `groupId`;";

        return $this->db()->fetchAll($sql, array($taskId)) ?: array();
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array(
                'taskId = :taskId',
                'groupId = :groupId',
            )
        );
    }
}
