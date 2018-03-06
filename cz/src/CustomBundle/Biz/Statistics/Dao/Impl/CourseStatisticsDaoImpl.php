<?php

namespace CustomBundle\Biz\Statistics\Dao\Impl;

use Codeages\Biz\Framework\Dao\GeneralDaoImpl;
use CustomBundle\Biz\Statistics\Dao\CourseStatisticsDao;

class CourseStatisticsDaoImpl extends GeneralDaoImpl implements CourseStatisticsDao
{
    protected $table = 'czie_course_statistics';

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId));
    }

    public function findTeachingAimWarningCoursesByValue($value)
    {
        $sql = "SELECT * FROM (SELECT courseId, avg(teachingAimsFinishedRate) AS avg FROM {$this->table} GROUP BY courseId) t WHERE t.avg < ?";

        return $this->db()->fetchAll($sql, array($value));
    }

    public function getByLessonId($lessonId)
    {
        return $this->getByFields(array('lessonId' => $lessonId));
    }

    public function countAvgByCourseIdsAndColumns($courseIds, $columns)
    {
        if (empty($courseIds)) {
            return null;
        }
        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT AVG({$columns}) FROM {$this->table} WHERE {$columns} is not NULL AND `courseId` IN ({$marks})";

        return $this->db()->fetchColumn($sql, $courseIds);
    }

    public function findByTermCodeAndWarnValue($termCode, $WarnValue)
    {
        $sql = "SELECT courseId,avgRate FROM (SELECT s.courseId,AVG(s.taskInCompletionRate) as avgRate FROM {$this->table} s LEFT JOIN course_v8 c on s.courseId = c.id LEFT JOIN course_set_v8 cs on cs.id = c.courseSetId WHERE cs.courseNo is not null AND c.termCode = ? group by courseId) as inComple where avgRate < ?";

        return $this->db()->fetchAll($sql, array($termCode, $WarnValue));
    }

    public function declares()
    {
        return array(
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'courseId = :courseId',
                'lessonId = :lessonId',
                'studentAttendRatio = :studentAttendRate'
            )
        );
    }
}
