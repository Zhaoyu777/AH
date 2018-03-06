<?php

namespace CustomBundle\Biz\Lesson\Dao\Impl;

use CustomBundle\Biz\Lesson\Dao\CourseTeachingAimWarningDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class CourseTeachingAimWarningDaoImpl extends AdvancedDaoImpl implements CourseTeachingAimWarningDao
{
    protected $table = 'zhkt_course_teaching_aim_finish_rate_warning';

    public function waveByCourseIds(array $courseIds, array $diffs)
    {
        $sets = array_map(
            function ($name) {
                return "{$name} = {$name} + ?";
            },
            array_keys($diffs)
        );

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "UPDATE {$this->table()} SET ".implode(', ', $sets)." WHERE courseId IN ($marks)";

        return $this->db()->executeUpdate($sql, array_merge(array_values($diffs), $courseIds));
    }

    public function deleteByCourseIds($courseIds)
    {
        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "DELETE FROM {$this->table} WHERE id IN ({$marks})";

        return $this->db()->executeUpdate($sql, $courseIds);
    }

    public function findAllWarningCourses()
    {
        $sql = "SELECT * FROM {$this->table}";

        return $this->db()->fetchAll($sql);
    }

    public function findByCourseIds($courseIds)
    {
        return $this->findInField('courseId', $courseIds);
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
