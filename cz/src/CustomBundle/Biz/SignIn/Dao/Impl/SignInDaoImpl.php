<?php

namespace CustomBundle\Biz\SignIn\Dao\Impl;

use CustomBundle\Biz\SignIn\Dao\SignInDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class SignInDaoImpl extends GeneralDaoImpl implements SignInDao
{
    protected $table = 'czie_lesson_signin';

    public function getLastSignInByLessonId($lessonId)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE  lessonId = ? ORDER BY `time` DESC limit 1";

        return $this->db()->fetchAssoc($sql, array($lessonId));
    }

    public function getByLessonIdAndTime($lessonId, $time)
    {
        return $this->getByFields(array(
            'lessonId' => $lessonId,
            'time' => $time,
        ));
    }

    public function deleteByLessonId($lessonId)
    {
        $sql = "DELETE FROM {$this->table} WHERE lessonId = ?";

        $this->db()->executeUpdate($sql, array($lessonId));
    }

    public function findIngByLessonIds($lessonIds)
    {
        $marks = str_repeat('?,', count($lessonIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` IN ({$marks}) AND status = 'start' ORDER BY `createdTime`;";

        return $this->db()->fetchAll($sql, $lessonIds);
    }

    public function findEndByLessonIds($lessonIds)
    {
        $marks = str_repeat('?,', count($lessonIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` IN ({$marks}) AND status = 'end'";

        return $this->db()->fetchAll($sql, $lessonIds);
    }

    public function findByLessonId($lessonId)
    {
        return $this->findByFields(array('lessonId' => $lessonId));
    }

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId));
    }

    public function findEndSignInsByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId, 'status' => 'end'));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(),
        );
    }
}
