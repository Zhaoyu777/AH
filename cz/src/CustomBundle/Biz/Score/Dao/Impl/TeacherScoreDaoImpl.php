<?php

namespace CustomBundle\Biz\Score\Dao\Impl;

use CustomBundle\Biz\Score\Dao\TeacherScoreDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class TeacherScoreDaoImpl extends GeneralDaoImpl implements TeacherScoreDao
{
    protected $table = 'czie_teacher_score';

    public function findByUserId($userId)
    {
        return $this->findByFields(array('userId' => $userId));
    }

    public function findByTermAndUserId($term, $userId)
    {
        return $this->findByFields(array('term' => $term, 'userId' => $userId));
    }

    public function sumScoreByTermAndUserId($term, $userId)
    {
        $sql = "SELECT sum(score) as scores FROM {$this->table} WHERE `term` = ? AND `userId` = ?";

        return $this->db()->fetchColumn($sql, array($term, $userId)) ? : 0;
    }

    public function getByLessonAndUserIdAndSource($lessonId, $userId, $source)
    {
        return $this->getByFields(array('lessonId' => $lessonId, 'userId' => $userId, 'source' => $source));
    }

    public function findByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->findByFields(array(
            'lessonId' => $lessonId,
            'userId' => $userId,
        ));
    }

    public function findUserSumScoresByCourseId($courseId)
    {
        $sql = "SELECT userId, sum(score) as scores FROM {$this->table} where courseId = ? GROUP BY userId";

        return $this->db()->fetchAll($sql, array($courseId));
    }

    public function sumScoreByCourseSetId($courseIds)
    {
        if (empty($courseIds)) {
            return 0;
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';
        $sql = "SELECT sum(score) as score FROM {$this->table} where courseId IN ({$marks}) AND userId <> 0";

        return $this->db()->fetchColumn($sql, $courseIds) ? : 0;
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'courseId IN (:courseIds)',
                'lessonId = :lessonId',
                'term = :term',
                'userId = :userId',
                'userId <> :unUserId'
            )
        );
    }
}
