<?php

namespace CustomBundle\Biz\SignIn\Dao\Impl;

use CustomBundle\Biz\SignIn\Dao\SignInMemberDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class SignInMemberDaoImpl extends AdvancedDaoImpl implements SignInMemberDao
{
    protected $table = 'czie_lesson_signin_member';

    public function deleteBySignInId($signInId)
    {
        return $this->db()->delete($this->table(), array('signinId' => $signInId));
    }

    public function deleteByLessonId($lessonId)
    {
        $sql = "DELETE FROM {$this->table} WHERE lessonId = ?";

        $this->db()->executeUpdate($sql, array($lessonId));
    }

    public function getBySignInIdAndUserId($signInId, $userId)
    {
        return $this->getByFields(array('signinId' => $signInId, 'userId' => $userId));
    }

    public function findBySignInId($signInId)
    {
        return $this->findByFields(array('signinId' => $signInId));
    }

    public function findByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->findByFields(array('lessonId' => $lessonId, 'userId' => $userId));
    }

    public function findByUserId($userId)
    {
        return $this->findByFields(array('userId' => $userId));
    }

    public function findByUserIdAndLessonIds($userId, $lessonIds)
    {
        if (empty($lessonIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($lessonIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE userId = ? AND lessonId IN ({$marks}) ORDER BY `updatedTime` DESC;";
        $fields = array_merge(array($userId), $lessonIds);

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function findBySignInIdAndStatus($signInId, $status)
    {
        return $this->findByFields(array('signinId' => $signInId, 'status' => $status));
    }

    public function findByLessonIdAndTimeAndStatus($lessonId, $time, $status, $count)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` = ? AND `time` = ? AND `status` = ? ORDER BY `id` DESC LIMIT {$count}";

        return $this->db()->fetchAll($sql, array($lessonId, $time, $status));
    }

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId, 'status' => 'attend'));
    }

    public function findByUserIdAndCourseIdAndStatus($userId, $courseId, $status)
    {
        return $this->findByFields(array('userId' => $userId, 'courseId' => $courseId, 'status' => $status));
    }

    public function countByCourseIdGroupUserIdBeforeTime($courseId, $time)
    {
        $sql = "SELECT count(id) count, userId FROM {$this->table} WHERE courseId = ? AND `time` = 1 AND createdTime < {$time} GROUP BY userId";

        return $this->db()->fetchAll($sql, array($courseId));
    }

    public function findUniqueMembersByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT DISTINCT(userId) AS userId,lessonId,courseId FROM {$this->table} WHERE courseId IN ({$marks})";

        return $this->db()->fetchAll($sql, $courseIds) ?: array();
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'signinId = :signinId',
                'lessonId = :lessonId',
                'status = :status',
                'time = :time',
            ),
        );
    }
}
