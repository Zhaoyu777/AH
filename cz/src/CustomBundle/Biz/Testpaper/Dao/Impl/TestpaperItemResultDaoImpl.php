<?php

namespace CustomBundle\Biz\Testpaper\Dao\Impl;

use Biz\Testpaper\Dao\Impl\TestpaperItemResultDaoImpl as BaseDaoImpl;

class TestpaperItemResultDaoImpl extends BaseDaoImpl
{
    public function deleteByResultId($resultId)
    {
        return $this->db()->delete($this->table(), array('resultId' => $resultId));
    }

    public function findByActivityIds($activityIds)
    {
        return $this->findInField('activityId', $activityIds);
    }

    public function findItemResultsByTestId($testId)
    {
        return $this->findByFields(array('testId' => $testId));
    }

    public function findItemResultsByResultIds($resultIds)
    {
        if (empty($resultIds)) {
            return array();
        }
        $marks = str_repeat('?,', count($resultIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `resultId` in ({$marks})";

        return $this->db()->fetchAll($sql, $resultIds);
    }

    public function findByCourseTaskId($courseTaskId)
    {
        return $this->findByFields(array('courseTaskId' => $courseTaskId));
    }
}
