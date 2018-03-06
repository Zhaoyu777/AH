<?php

namespace CustomBundle\Biz\Lesson\Dao\Impl;

use CustomBundle\Biz\Lesson\Dao\TeachingAimDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class TeachingAimDaoImpl extends AdvancedDaoImpl implements TeachingAimDao
{
    protected $table = 'zhkt_lesson_teaching_aims';

    public function getAimByParentIdAndLessonId($parentId, $lessonId)
    {
        return $this->getByFields(array(
            'parentId' => $parentId,
            'lessonId' => $lessonId
        ));
    }

    public function findAimsByLessonId($lessonId)
    {
        $sql = "SELECT * FROM {$this->table} where lessonId = ? ORDER BY `number` ASC";

        return $this->db()->fetchAll($sql, array($lessonId)) ?: array();
    }

    public function findAimsByAimIds($aimIds)
    {
        return $this->findInField('id', $aimIds);
    }

    public function findAimsByParentIds($aimIds)
    {
        return $this->findInField('parentId', $aimIds);
    }

    public function findUniqueCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT courseId FROM {$this->table} WHERE courseId IN ({$marks}) GROUP BY courseId";

        return $this->db()->fetchAll($sql, $courseIds);
    }

    public function findAimsByCourseIdAndTermCode($courseId, $termCode)
    {
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND termCode = ?";

        return $this->db()->fetchAll($sql, array($courseId, $termCode));
    }

    public function findAimsByLessonIdAndTermCode($lessonId, $termCode)
    {
        $sql = "SELECT * FROM {$this->table} WHERE lessonId = ? AND termCode = ?";

        return $this->db()->fetchAll($sql, array($lessonId, $termCode));
    }

    public function findAimsByOrgCodeAndTermCode($orgCode, $termCode)
    {
        $sql = "SELECT * FROM {$this->table} WHERE orgCode = ? AND termCode = ?";

        return $this->db()->fetchAll($sql, array($orgCode, $termCode));
    }

    public function findAllAims()
    {
        $sql = "SELECT * FROM {$this->table}";

        return $this->db()->fetchAll($sql);
    }

    public function countCourseOwnedAimsByCourseIdsAndTermCode($courseIds, $termCode)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT courseId, count(*) AS count FROM {$this->table} WHERE courseId IN ({$marks}) AND termCode = ? GROUP BY courseId";

        return $this->db()->fetchAll($sql, array_merge(array_values($courseIds), array($termCode)));
    }

    public function deleteAimsByLessonId($lessonId)
    {
        $sql = "DELETE FROM {$this->table} WHERE lessonId = ?";

        return $this->db()->executeUpdate($sql, array($lessonId));
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
