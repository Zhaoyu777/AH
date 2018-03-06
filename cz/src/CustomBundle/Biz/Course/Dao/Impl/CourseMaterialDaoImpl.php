<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use Biz\Course\Dao\Impl\CourseMaterialDaoImpl as BaseCourseMaterialDaoImpl;
use CustomBundle\Biz\Course\Dao\CourseMaterialDao;

class CourseMaterialDaoImpl extends BaseCourseMaterialDaoImpl implements CourseMaterialDao
{
    public function countStatisticsByCourseIdsAndUserId($courseIds, $userId)
    {
        if (empty($courseIds)) {
            return 0;
        }
        $marks = str_repeat('?,', count($courseIds) - 1).'?';
        $sql = "SELECT count(distinct fileId) FROM {$this->table} m LEFT JOIN `upload_files` f ON m.fileId = f.id WHERE `courseId` IN ({$marks}) AND `createdUserId` = ?";
        $fields = array_merge($courseIds, array($userId));

        return $this->db()->fetchColumn($sql, $fields) ? : 0;
    }

    public function countStatisticsByUserId($userId)
    {
        $sql = "SELECT count(f.id) FROM {$this->table} m LEFT JOIN `upload_files` f ON m.fileId = f.id WHERE `createdUserId` = ? AND `source` = 'courseactivity'";

        return $this->db()->fetchColumn($sql, array($userId)) ? : 0;
    }

    public function countCourseSetAllFilesByCourseSetId($courseSetId)
    {
        $sql = "SELECT COUNT(distinct(fileId)) FROM {$this->table} WHERE courseSetId = ?";

        return $this->db()->fetchColumn($sql, array($courseSetId));
    }

    public function countMaterialByUserId($userId)
    {
        $sql = "SELECT count(distinct(fileId)) FROM {$this->table} WHERE userId = ?";

        return $this->db()->fetchColumn($sql, array($userId));
    }

    public function findByLessonId($lessonId)
    {
        return $this->findByFields(array('lessonId' => $lessonId));
    }
}
