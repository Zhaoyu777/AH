<?php

namespace CustomBundle\Biz\Lesson\Dao\Impl;

use CustomBundle\Biz\Lesson\Dao\EvaluationDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class EvaluationDaoImpl extends GeneralDaoImpl implements EvaluationDao
{
    protected $table = 'czie_lesson_evaluation';

    public function findByCourseIdAndUserId($courseId, $userId)
    {
        return $this->findByFields(array('courseId' => $courseId, 'studentId' => $userId));
    }

    public function getByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->getByFields(array('lessonId' => $lessonId, 'studentId' => $userId));
    }

    public function findByLessonId($lessonId)
    {
        return $this->findByFields(array('lessonId' => $lessonId));
    }

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId));
    }

    public function getScoreAvgByLessonId($lessonId)
    {
        $sql = "SELECT avg(score) FROM `{$this->table}` WHERE `lessonId` = ?";

        return $this->db()->fetchColumn($sql, array($lessonId));
    }

    public function findCourseIdsBetweenFromTimeAndToTime($fromTime, $toTime)
    {
        $sql = "SELECT courseId FROM {$this->table} WHERE createdTime between ? and ? GROUP BY courseId";

        return $this->db()->fetchAssoc($sql, array($fromTime, $toTime)) ?: array();
    }

    public function getCourseAverageByUserId($courseId, $userId)
    {
        $sql = "SELECT FORMAT(AVG(score), 1) AS averageScore FROM {$this->table} WHERE courseId = ? ANd studentId = ? ";

        return $this->db()->fetchColumn($sql, array($courseId, $userId)) ? : 0;
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array(),
        );
    }
}
