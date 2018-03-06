<?php

namespace CustomBundle\Biz\Score\Dao\Impl;

use CustomBundle\Biz\Score\Dao\ScoreDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ScoreDaoImpl extends GeneralDaoImpl implements ScoreDao
{
    protected $table = 'czie_user_score';

    public function deleteByTargetTypeAndTargetId($targetType, $targetId)
    {
        return $this->db()->delete($this->table(), array('targetId' => $targetId, 'targetType' => $targetType));
    }

    public function getByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getByFields(array(
            'taskId' => $taskId,
            'userId' => $userId,
        ));
    }

    public function deleteByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE taskId IN ({$marks});";

        return $this->db()->executeUpdate($sql, $taskIds);
    }

    public function findByUserId($userId)
    {
        return $this->findByFields(array('userId' => $userId));
    }

    public function findByTermAndUserId($term, $userId, $start, $limit)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `term` = ? AND `userId` = ? ORDER BY `createdTime` DESC LIMIT {$start}, {$limit}";

        return $this->db()->fetchAll($sql, array($term, $userId)) ?: array();
    }

    public function findByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->findByFields(array(
            'lessonId' => $lessonId,
            'userId' => $userId,
        ));
    }

    public function findByLessonId($lessonId, $start, $limit)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` = ? LIMIT {$start}, {$limit}";

        return $this->db()->fetchAll($sql, array($lessonId));
    }

    public function findUserSumScoresByCourseId($courseId)
    {
        $sql = "SELECT userId, sum(score) as scores FROM {$this->table} where courseId = ? GROUP BY userId";

        return $this->db()->fetchAll($sql, array($courseId));
    }

    public function findUserSumScoresByLessonId($lessonId)
    {
        $sql = "SELECT userId, sum(score) as scores FROM {$this->table} where lessonId = ? GROUP BY userId";

        return $this->db()->fetchAll($sql, array($lessonId));
    }

    public function sumScoresByLessonId($lessonId)
    {
        $sql = "SELECT sum(score) as scores FROM {$this->table} WHERE `lessonId` = ?";

        return $this->db()->fetchColumn($sql, array($lessonId)) ?: 0;
    }

    public function sumByTermAndUserId($term, $userId)
    {
        $sql = "SELECT sum(score) as scores FROM {$this->table} WHERE `term` = ? AND `userId` = ?";

        return $this->db()->fetchColumn($sql, array($term, $userId)) ?: 0;
    }

    public function countUserByLessonId($lessonId)
    {
        $sql = "SELECT count(distinct userId) as userCount FROM {$this->table} WHERE `lessonId` = ? AND score > 0 ";

        return $this->db()->fetchColumn($sql, array($lessonId)) ?: 0;
    }

    public function sumStudentsScoresByCourseId($courseId)
    {
        $sql = "SELECT sum(score) AS score,userId FROM {$this->table} WHERE courseId = ? GROUP BY userId";

        return $this->db()->fetchAll($sql, array($courseId)) ?: array();
    }

    public function countStudentsScoresByUserIdAndLessonIds($userId, $lessonIds)
    {
        $marks = str_repeat('?,', count($lessonIds) - 1).'?';

        $sql = "SELECT sum(score) AS score FROM {$this->table} WHERE userId = ? AND lessonId IN ({$marks})";

        return $this->db()->fetchColumn($sql, array_merge(array($userId), $lessonIds)) ?: 0;
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'lessonId = :lessonId',
                'term = :term',
                'userId = :userId',
                'score >= :minScore',
            ),
        );
    }
}
