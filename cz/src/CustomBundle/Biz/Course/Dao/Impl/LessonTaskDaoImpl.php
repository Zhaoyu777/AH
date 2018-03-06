<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\LessonTaskDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class LessonTaskDaoImpl extends GeneralDaoImpl implements LessonTaskDao
{
    protected $table = 'czie_course_lesson_task';

    public function resetLessonTasksChapter($chapterId)
    {
        $sql = "UPDATE {$this->table()} SET `chapterId` = '0' WHERE chapterId = ?;";

        return $this->db()->executeUpdate($sql, array($chapterId));
    }

    public function getFirstInClassByLessonId($lessonId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `lessonId` = ? AND `stage` = 'in' ORDER BY `seq` ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId)) ?: array();
    }

    public function countByLessonIds($lessonIds)
    {
        if (empty($lessonIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($lessonIds) - 1).'?';

        $sql = "SELECT lessonId, COUNT(id) as count FROM {$this->table} WHERE `lessonId` IN ({$marks}) GROUP BY lessonId";

        return $this->db()->fetchAll($sql, $lessonIds) ?: array();
    }

    public function findByLessonId($lessonId)
    {
        return $this->findByFields(array('lessonId' => $lessonId));
    }

    public function countStatisticsByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return 0;
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT count(DISTINCT lessonId) FROM {$this->table} WHERE `courseId` IN ({$marks})";

        return $this->db()->fetchColumn($sql, $courseIds) ?: 0;
    }

    public function findInTasksByLessonId($lessonId)
    {
        return $this->findByFields(array(
            'lessonId' => $lessonId,
            'stage' => 'in',
        ));
    }

    public function findTasksByLessonIdAndStage($lessonId, $stage)
    {
        return $this->findByFields(array(
            'lessonId' => $lessonId,
            'stage' => $stage,
        ));
    }

    public function getByTaskId($taskId)
    {
        return $this->getByFields(array('taskId' => $taskId));
    }

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId));
    }

    public function findTeachedLessonTasksByCourseId($courseId)
    {
        $sql = "SELECT * FROM {$this->table} m LEFT JOIN czie_course_lesson n ON m.lessonId = n.id WHERE (n.status = 'teached' AND m.courseId = ?)";

        return $this->db()->fetchAll($sql, array($courseId)) ?: array();
    }

    public function findInByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE stage = 'in' AND taskId in ({$marks})";

        return $this->db()->fetchAll($sql, $taskIds);
    }

    public function findByTaskIds($taskIds)
    {
        return $this->findInField('taskId', $taskIds);
    }

    public function findSchoolTasksByTermCode($termCode)
    {
        $sql = "SELECT lessonId FROM {$this->table} cl LEFT JOIN `course_v8` c ON c.id = cl.courseId LEFT JOIN `course_set_v8` cs ON cs.id = c.courseSetId WHERE cs.orgCode LIKE '1.%' AND c.termCode = ? GROUP BY lessonId";

        return $this->db()->fetchAll($sql, array($termCode)) ?: array();
    }

    public function findLessonTasksByLessonIds($lessonIds)
    {
        $marks = str_repeat('?,', count($lessonIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE id IN ({$marks})";

        return $this->db()->fetchAll($sql, $lessonIds);
    }

    public function findOutLessonTasksByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE taskId In ({$marks}) AND stage <> 'in'";

        return $this->db()->fetchAll($sql, $taskIds);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'lessonId = :lessonId',
                'stage = :stage',
                'courseId = :courseId',
                'chapterId = :chapterId',
            )
        );
    }
}
