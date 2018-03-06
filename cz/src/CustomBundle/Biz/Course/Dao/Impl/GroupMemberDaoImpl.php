<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\GroupMemberDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class GroupMemberDaoImpl extends GeneralDaoImpl implements GroupMemberDao
{
    protected $table = 'czie_student_group_member';

    public function getByCourseMemberId($courseMemberId)
    {
        return $this->getByFields(array('courseMemberId' => $courseMemberId));
    }

    public function deleteByGroupId($groupId)
    {
        return $this->db()->delete($this->table(), array('groupId' => $groupId));
    }

    public function getMaxSeqByGroupId($groupId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `groupId` = ? ORDER BY `seq` DESC LIMIT 1";
        return $this->db()->fetchAssoc($sql, array($groupId)) ?: array();
    }

    public function findByGroupIds($groupIds)
    {
        if (empty($groupIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($groupIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE `groupId` IN ({$marks}) ORDER BY `seq` ASC";

        return $this->db()->fetchAll($sql, $groupIds) ?: array();
    }

    public function findByGroupIdAndCourseMemberIds($groupId, $courseMemberIds)
    {
        $marks = str_repeat('?,', count($courseMemberIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `courseMemberId` IN ({$marks}) AND groupId = ? ORDER BY `createdTime`;";
        $fields = array_merge($courseMemberIds, array($groupId));

        return $this->db()->fetchAll($sql, $fields);
    }

    public function resetGroupMember($groupId, $defaultGroupId)
    {
        $sql = "UPDATE {$this->table} set `groupId` = ? WHERE `groupId` = ? ";

        return $this->db()->executeUpdate($sql, array($defaultGroupId, $groupId));
    }

    public function deleteByCourseMemberId($courseMemberId)
    {
        return $this->db()->delete($this->table(), array('courseMemberId' => $courseMemberId));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'groupId =:groupId',
            )
        );
    }
}
