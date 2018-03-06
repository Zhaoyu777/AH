<?php

namespace CustomBundle\Biz\Task\Dao\Impl;

use Biz\Task\Dao\Impl\TaskResultDaoImpl as BaseTaskResultDaoImpl;

class ResultDaoImpl extends BaseTaskResultDaoImpl
{
    public function findByTaskId($taskId)
    {
        return $this->getByFields(array('courseTaskId' => $taskId));
    }

    public function findCourseIdsBetweenFromTimeAndToTime($from, $toTime)
    {
        $sql = "SELECT courseId FROM {$this->table} WHERE finishedTime between ? and ? GROUP BY courseId";

        return $this->db()->fetchAssoc($sql, array($from, $toTime)) ?: array();
    }

    public function deleteByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE courseTaskId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $taskIds);
    }

    public function findByUserIdsAndTaskId($userIds, $taskId)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE courseTaskId = ? AND userId IN ({$marks})";
        $fields = array_merge(array($taskId), $userIds);

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function findStudentResultByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE status = 'finish' AND `courseTaskId` IN ({$marks})";

        return $this->db()->fetchAll($sql, $taskIds) ?: array();
    }

    public function countStudentResultByTaskId($taskId)
    {
        $sql = "SELECT count(t.id) as count FROM {$this->table} t LEFT JOIN `user` u ON u.id = t.userId WHERE u.roles NOT LIKE '%ROLE_TEACHER%' AND `courseTaskId` = ?";

        return $this->db()->fetchColumn($sql, array($taskId)) ?: 0;
    }

    public function countStudentResultByTaskIdAndStatusAndTeacherIds($taskId, $status, $teacherIds)
    {
        $marks = str_repeat('?,', count($teacherIds) - 1).'?';
        $sql = "SELECT count(id) as count FROM {$this->table} WHERE status = ? AND `courseTaskId` = ? AND userId NOT IN({$marks})";
        $fields = array_merge(array($status), array($taskId), $teacherIds);

        return $this->db()->fetchColumn($sql, $fields) ?: 0;
    }

    public function findByTaskIdsAndUserId($taskIds, $userId)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE userId = ? AND courseTaskId IN ({$marks})";
        $fields = array_merge(array($userId), $taskIds);

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function getLatestByCourseIdAndUserIds($courseId, $userIds)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND userId IN ({$marks}) ORDER BY `createdTime` DESC LIMIT 1";
        $fields = array_merge(array($courseId), $userIds);

        return $this->db()->fetchAssoc($sql, $fields) ?: null;
    }

    public function findByTaskIdsAndUserIds($taskIds, $userIds)
    {
        $taskIdMarks = str_repeat('?,', count($taskIds) - 1).'?';
        $userIdMarks = str_repeat('?,', count($userIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE `courseTaskId` IN ({$taskIdMarks}) AND userId IN ({$userIdMarks})";
        $fields = array_merge($taskIds, $userIds);

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function findByTime($startTime, $endTime)
    {
        $sql = "SELECT courseId FROM `{$this->table}` WHERE `updatedTime`>= ? AND `updatedTime`<= ? group by courseId";

        return $this->db()->fetchAll($sql, array($startTime, $endTime));
    }

    public function findResultsByCourseIdAndUserIds($courseId, $userIds)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND userId in ({$marks})";

        $fields = array();
        $fields[] = $courseId;
        $fields = array_merge($fields, $userIds);

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function getLastResultByCourseIdAndUserId($courseId, $userId, $createdTime)
    {
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND userId = ? AND createdTime < ? ORDER BY `createdTime` DESC LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($courseId, $userId, $createdTime));
    }

    public function getByCourseIdAndTaskId($courseId, $taskId)
    {
        return $this->getByFields(array(
            'courseId' => $courseId,
            'courseTaskId' => $taskId
        ));
    }

    public function findFinishedResultsByCourseIdAndUserId($courseId, $userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND userId = ? AND status = 'finish'";

        return $this->db()->fetchAll($sql, array($courseId, $userId));
    }

    public function countStudentResultByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "SELECT courseTaskId, count(courseTaskId) as count FROM {$this->table} WHERE status = 'finish' AND `courseTaskId` IN ({$marks}) GROUP BY courseTaskId";

        return $this->db()->fetchAll($sql, $taskIds) ?: array();
    }
}
