<?php

namespace CustomBundle\Biz\Statistics\Dao\Impl;

use CustomBundle\Biz\Statistics\Dao\StudentCourseStatisticsDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class StudentCourseStatisticsDaoImpl extends GeneralDaoImpl implements StudentCourseStatisticsDao
{
    protected $table = 'czie_student_course_statistics';

    public function getStudentsMultiAnalysisByCourseId($courseId)
    {
        $sql = "SELECT FORMAT(AVG(studentAttendence), 1) AS studentAttendence, FORMAT(AVG(taskInCompletionRate), 1) AS taskInCompletionRate, FORMAT(AVG(taskOutCompletionRate), 1) AS taskOutCompletionRate, FORMAT(AVG(averageGrades), 1) AS averageGrades FROM {$this->table} WHERE courseId = ?";

        return $this->db()->fetchAssoc($sql, array($courseId)) ?: array();
    }

    public function getByUserIdAndCourseId($userId, $courseId)
    {
        return $this->getByFields(
            array(
                'userId' => $userId,
                'courseId' => $courseId
            )
        );
    }

    public function declares()
    {
        return array(
            'timestamps' => array('createdTime', 'updatedTime'),
            'orderbys' => array(
                'studentAttendence',
                'taskInCompletionRate',
                'taskOutCompletionRate',
                'averageGrades',
                'totalScore',
            ),
            'conditions' => array(
                'courseId = :courseId',
                'userId IN (:userIds)',
                'studentAttendence >= :gtstudentAttendence',
                'taskInCompletionRate >= :gttaskInCompletionRate',
                'taskOutCompletionRate >= :gttaskOutCompletionRate',
                'studentAttendence < :ltstudentAttendence',
                'taskInCompletionRate < :lttaskInCompletionRate',
                'taskOutCompletionRate < :lttaskOutCompletionRate',
                'studentAttendence = :studentAttendence',
                'taskInCompletionRate = :taskInCompletionRate',
                'taskOutCompletionRate = :taskOutCompletionRate',
            ),
        );
    }
}
