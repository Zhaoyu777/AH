<?php

namespace CustomBundle\Biz\Lesson\Dao\Impl;

use CustomBundle\Biz\Lesson\Dao\TeachingAimActivityDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class TeachingAimActivityDaoImpl extends AdvancedDaoImpl implements TeachingAimActivityDao
{
    protected $table = 'zhkt_lesson_teaching_aims_activity';

    public function findRelationsByActivityId($activityId)
    {
        return $this->findByFields(array(
            'activityId' => $activityId
        ));
    }

    public function findRelationsByCourseIdAndTermCode($courseId, $termCode)
    {
        $sql = "SELECT aimId FROM {$this->table} WHERE courseId = ? AND termCode = ? GROUP BY aimId";

        return $this->db()->fetchAll($sql, array($courseId, $termCode));
    }

    public function findRelationsByLessonIdAndTermCode($lessonId, $termCode)
    {
        $sql = "SELECT aimId FROM {$this->table} WHERE lessonId = ? AND termCode = ? GROUP BY aimId";

        return $this->db()->fetchAll($sql, array($lessonId, $termCode));
    }

    public function findRelationsByOrgCodeAndTermCode($orgCode, $termCode)
    {
        $sql = "SELECT aimId FROM {$this->table} WHERE orgCode = ? AND termCode = ? GROUP BY aimId";

        return $this->db()->fetchAll($sql, array($orgCode, $termCode));
    }

    public function countRelationsByCourseIdsAndTeacherIdAndTermCode($courseIds, $teacherId, $termCode)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT courseId, aimId FROM {$this->table} WHERE courseId IN ({$marks}) AND teacherId = ? AND termCode = ? GROUP BY aimId";

        return $this->db()->fetchAll($sql, array_merge(array_values($courseIds), array($teacherId), array($termCode)));
    }

    public function deleteRelationsByAimId($aimId)
    {
        $sql = "DELETE FROM {$this->table} WHERE aimId = ?";

        return $this->db()->executeUpdate($sql, array($aimId));
    }

    public function deleteRelationsByActivityId($activityId)
    {
        $sql = "DELETE FROM {$this->table} WHERE activityId = ?";

        return $this->db()->executeUpdate($sql, array($activityId));
    }

    public function declares()
    {
        return array(
            'timestamps' => array(
                'createdTime',
                'updatedTime'
            ),
        );
    }
}
