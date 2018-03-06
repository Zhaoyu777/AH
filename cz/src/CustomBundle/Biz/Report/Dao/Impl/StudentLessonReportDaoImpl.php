<?php

namespace CustomBundle\Biz\Report\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use CustomBundle\Biz\Report\Dao\StudentLessonReportDao;

class StudentLessonReportDaoImpl extends AdvancedDaoImpl implements StudentLessonReportDao
{
    protected $table = 'czie_student_lesson_report';

    public function findBylessonId($lessonId)
    {
        return $this->findByFields(array(
            'lessonId' => $lessonId,
        ));
    }

    public function getBylessonIdAndUserId($lessonId, $userId)
    {
        return $this->getByFields(array(
            'lessonId' => $lessonId,
            'userId' => $userId,
        ));
    }

    public function findBycourseId($courseId)
    {
        return $this->findByFields(array(
            'courseId' => $courseId,
        ));
    }

    public function findBycourseIdAndUserId($courseId, $userId)
    {
        return $this->findByFields(array(
            'courseId' => $courseId,
            'userId' => $userId,
        ));
    }

    public function freshReports()
    {
        $sql = "truncate {$this->table}";

        return $this->db()->exec($sql);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'courseId = :courseId',
                'lessonId = :lessonId',
                'userId = :userId',
                'taskInCompletionRate < :minTaskInCompletionRate',
                'taskBeforCompletionRate < :minTaskBeforCompletionRate',
                'exerciseNumber < :minExerciseNumber',
            ),
        );
    }
}