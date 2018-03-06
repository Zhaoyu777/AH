<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use Biz\Course\Dao\Impl\CourseChapterDaoImpl as BaseCourseChapterDaoImpl;

class CourseChapterDaoImpl extends BaseCourseChapterDaoImpl
{
    public function getChapterMaxSeqByLessonIdAndStageAndParentId($lessonId, $parnetId, $stage)
    {
        $sql = "SELECT MAX(seq) FROM {$this->table()} WHERE  lessonId = ? AND parentId = ? AND stage = ? ";

        return $this->db()->fetchColumn($sql, array($lessonId, $parnetId, $stage));
    }

    public function getNextChapterByLessonIdAndSeq($lessonId, $seq)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE  lessonId = ? AND type = 'lesson' AND seq > ? ORDER BY `seq` ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId, $seq));
    }

    public function getLastChapterByLessonIdAndSeq($lessonId, $seq)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE  lessonId = ? AND type = 'lesson' AND seq < ? ORDER BY `seq` DESC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId, $seq));
    }

    public function getFirstInClassTaskChapterByLessonId($lessonId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` = ? AND `stage` = 'in' AND `type` = 'lesson' ORDER BY `seq` ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId)) ?: array();
    }

    public function getFirstBeforeClassTaskChapterByLessonId($lessonId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` = ? AND `stage` = 'before' AND `type` = 'lesson' ORDER BY `seq` ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId)) ?: array();
    }

    public function getFirstUnFinishedBeforeClassTaskChapterByLessonId($lessonId, $userId)
    {
        $sql = "SELECT cc.* FROM {$this->table} cc
            LEFT JOIN  `course_task` ct ON ct.categoryId = cc.id
            LEFT JOIN  `course_task_result` ctr ON ctr.courseTaskId = ct.id
            WHERE cc.lessonId = ?
            AND cc.stage = 'before'
            AND cc.type = 'lesson'
            AND ctr.status =  'start'
            AND ctr.userId = ?
            ORDER BY cc.seq ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId, $userId)) ?: array();
    }

    public function getFirstAfterClassTaskChapterByLessonId($lessonId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` = ? AND `stage` = 'after' AND `type` = 'lesson' ORDER BY `seq` ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId)) ?: array();
    }

    public function getFirstUnFinishedAfterClassTaskChapterByLessonId($lessonId, $userId)
    {
        $sql = "SELECT cc.* FROM {$this->table} cc
            LEFT JOIN  `course_task` ct ON ct.categoryId = cc.id
            LEFT JOIN  `course_task_result` ctr
            ON ctr.courseTaskId = ct.id
            AND ctr.status =  'start'
            WHERE cc.lessonId = ?
            AND cc.stage = 'after'
            AND cc.type = 'lesson'
            AND ctr.userId = ?
            ORDER BY cc.seq ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId, $userId)) ?: array();
    }

    public function findChaptersByLessonId($lessonId)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE lessonId = ? ORDER BY seq ASC";

        return $this->db()->fetchAll($sql, array($lessonId));
    }

    public function findByLessonIdAndTpye($lessonId, $type)
    {
        return $this->FindByFields(array('lessonId' => $lessonId, 'type' => $type));
    }

    public function getChapterByCourseIdAndLessonId($courseId, $lessonId)
    {
        return $this->getByFields(array(
            'courseId' => $courseId,
            'lessonId' => $lessonId
        ));
    }

    public function getNextChapter($courseId, $lessonId, $baseSeq)
    {
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND lessonId = ? AND type = 'lesson' AND seq > ? LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($courseId, $lessonId, $baseSeq));
    }

    public function getPreviousChapter($courseId, $lessonId, $baseSeq)
    {
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND lessonId = ? AND type = 'lesson' AND seq < ? LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($courseId, $lessonId, $baseSeq));
    }

    public function findChapterByCourseIdAndLessonId($courseId, $lessonId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND lessonId = ? AND type = 'lesson' ORDER BY `stage` ASC, `seq` ASC";

        return $this->db()->fetchAll($sql, array($courseId, $lessonId));
    }

    public function findChapterByLessonIdAndStage($lessonId, $stage)
    {
        return $this->findByFields(array(
            'stage' => $stage,
            'lessonId' => $lessonId
        ));
    }

    public function findChapterByCourseIdAndLessonIdAndIds($ids)
    {
        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND id IN ({$marks}) ORDER BY `stage` ASC, `seq` ASC";

        return $this->db()->fetchAll($sql, $ids);
    }

    public function countChapterByLessonIdAndSeqAndStage($lessonId, $baseSeq, $stage)
    {
        $sql = "SELECT count(id) FROM {$this->table} WHERE lessonId = ? AND type = 'lesson' AND stage = ? AND seq < ?";

        return $this->db()->fetchColumn($sql, array($lessonId, $stage, $baseSeq));
    }

    public function countChapterByLessonIdAndSeq($lessonId, $baseSeq)
    {
        $sql = "SELECT count(id) FROM {$this->table} WHERE lessonId = ? AND type = 'lesson' AND seq < ?";

        return $this->db()->fetchColumn($sql, array($lessonId, $baseSeq));
    }

    public function countChapterByLessonIdAndStage($lessonId, $stage)
    {
        $sql = "SELECT count(id) FROM {$this->table} WHERE lessonId = ? AND type = 'lesson' AND stage = ?";

        return $this->db()->fetchColumn($sql, array($lessonId, $stage));
    }

    public function findByLessonIdAndStage($lessonId, $stage)
    {
        return $this->FindByFields(array('lessonId' => $lessonId, 'stage' => $stage));
    }

    public function getChapterCountByCourseIdAndTypeAndParentId($courseId, $type, $parentId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table()} WHERE  courseId = ? AND type = ? AND parentId = ?";

        return $this->db()->fetchColumn($sql, array($courseId, $type, $parentId));
    }
}
