<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\CourseMainTeacherDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class CourseMainTeacherDaoImpl extends AdvancedDaoImpl implements CourseMainTeacherDao
{
    protected $table = 'zhkt_course_main_teacher';

    public function getByCourseId($courseId)
    {
        return $this->getByFields(array(
            'courseId' => $courseId
        ));
    }

    public function findCoursesByTeacherId($teacherId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE teacherId = ?";

        return $this->db()->fetchAll($sql, array($teacherId));
    }

    public function findMainTeachersByCourseIds($courseIds)
    {
        return $this->findInField('courseId', $courseIds);
    }

    public function deleteByCourseId($courseId)
    {
        $sql = "DELETE FROM {$this->table} WHERE courseId = ?";

        return $this->db()->executeUpdate($sql, array($courseId));
    }

    public function findTeachersByCourseIds($courseIds)
    {
        if (!count($courseIds)) {
            return array();
        }
        $marks = str_repeat('?,', count($courseIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `courseId` IN ({$marks})";

        return $this->db()->fetchAll($sql, $courseIds) ?: array();
    }

    public function findAllMainTeachers()
    {
        $sql = "SELECT * FROM {$this->table}";

        return $this->db()->fetchAll($sql);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(),
        );
    }
}
