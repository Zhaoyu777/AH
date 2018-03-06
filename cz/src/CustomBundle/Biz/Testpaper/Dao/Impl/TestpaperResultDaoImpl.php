<?php

namespace CustomBundle\Biz\Testpaper\Dao\Impl;

use Biz\Testpaper\Dao\Impl\TestpaperResultDaoImpl as BaseDaoImpl;

class TestpaperResultDaoImpl extends BaseDaoImpl
{
    public function findByActivityIds($activityIds)
    {
        return $this->findInField('lessonId', $activityIds);
    }

    public function findItemResultsByTestIdAndLessonId($testId, $lessonId)
    {
        return $this->findByFields(array(
            'testId' => $testId,
            'lessonId' => $lessonId,
        ));
    }

    public function findItemResultsByResultId($resultId)
    {
        return $this->findByFields(array('resultId' => $resultId));
    }

    public function countByTestId($testId)
    {
        $sql = "SELECT count(id) FROM {$this->table} WHERE `testId` = ?";

        return $this->db()->fetchColumn($sql, array($testId)) ?: 0;
    }

    public function findByCourseId($courseId)
    {
        $sql = "SELECT * FROM {$this->table} m LEFT JOIN czie_course_lesson n ON m.lessonId = n.id WHERE (n.status = 'teached' AND m.courseId = ?)";

        return $this->db()->fetchAll($sql, array($courseId)) ?: array();
    }

    public function getLastResultByTestId($testId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `testId` = ? ORDER BY `updateTime` DESC LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($testId)) ?: null;
    }

    public function countTestpaperResultsByStatus($conditions)
    {
        $sql = "SELECT count(tr.id) FROM {$this->table} tr LEFT JOIN `course_v8` cv ON tr.courseId = cv.id WHERE tr.type = ? AND tr.userId = ? ";
        if ($conditions['courseType'] == 'instant') {
            $sql = $sql . " AND cv.type = 'instant'";
        } else {
            $sql = $sql . " AND cv.type != 'instant'";
        }
        
        return $this->db()->fetchColumn($sql, array($conditions['type'], $conditions['userId'])) ?: 0;
    }

    public function searchTestpaperResultsByStatus($conditions, $start, $limit)
    {
        $sql = "SELECT tr.* FROM {$this->table} tr LEFT JOIN `course_v8` cv ON tr.courseId = cv.id WHERE tr.type = ? AND tr.userId = ? ";
        if ($conditions['courseType'] == 'instant') {
            $sql = $sql. " AND cv.type = 'instant' ";
        } else {
            $sql = $sql. " AND cv.type != 'instant' ";
        }

        return $this->db()->fetchAll($sql, array($conditions['type'], $conditions['userId'])) ?: array();
    }

    public function countOnlineTestpaperResults($conditions)
    {
        $sql = "SELECT count(tr.id) FROM {$this->table()} tr LEFT JOIN course_v8 c ON c.id = tr.courseId WHERE tr.status = ? AND tr.type = ? AND tr.userId = ? AND c.type != 'instant'";


        return $this->db()->fetchColumn($sql, array($conditions['status'], $conditions['type'], $conditions['userId'])) ?: 0;
    }

    public function searchOnlineTestpaperResults($conditions, $start, $limit)
    {

        $sql = "SELECT tr.* FROM {$this->table()} tr LEFT JOIN course_v8 c ON c.id = tr.courseId WHERE tr.status = ? AND tr.type = ? AND tr.userId = ? AND c.type != 'instant'";


        return $this->db()->fetchAll($sql, array($conditions['status'], $conditions['type'], $conditions['userId'])) ?: array();
    }

    public function findResultsByCourseIdAndUserIdAndStatus($courseId, $userId, $status)
    {
        return $this->findByFields(array(
            'courseId' => $courseId,
            'userId' => $userId,
            'status' => $status,
            'type' => 'testpaper',
        ));
    }
}
