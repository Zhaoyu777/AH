<?php

namespace CustomBundle\Biz\Task\Dao\Impl;

use Biz\Task\Dao\TaskDao;
use Biz\Task\Dao\Impl\TaskDaoImpl as BaseTaskDaoImpl;

class TaskDaoImpl extends BaseTaskDaoImpl implements TaskDao
{
    public function getByCategoryId($categoryId)
    {
        return $this->getByFields(array('categoryId' => $categoryId));
    }

    public function findByCategoryIds($categoryIds)
    {
        return $this->findInField('categoryId', $categoryIds);
    }

    public function findInteractiveTaskByIds($taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $types = str_repeat('?,', count($this->getInteractiveType()) - 1).'?';

        $sql = "SELECT id, activityId, type FROM {$this->table} WHERE id IN ({$marks}) AND type IN ({$types})";

        $fields = array_merge($taskIds, $this->getInteractiveType());

        return $this->db()->fetchAll($sql, $fields) ?: array();
    }

    public function findStatisticsTaskCountByUserId($userId)
    {
        $sql = "SELECT courseId FROM {$this->table} WHERE createdUserId = ? group by courseId";

        return $this->db()->fetchAll($sql, array($userId)) ?: array();
    }

    public function findTasksByTaskType($taskType)
    {
        $sql = "SELECT clt.lessonId FROM {$this->table} ct LEFT JOIN `czie_course_lesson_task` clt ON clt.taskId = ct.id WHERE ct.type = ? GROUP BY clt.lessonId";

        return $this->db()->fetchAll($sql, array($taskType)) ?: array();
    }

    public function getFirstNotStartedBeforeTaskByLessonId($lessonId, $userId)
    {
        $sql = "SELECT ct.*
            FROM  `course_task` ct
            LEFT JOIN  `course_chapter` cc ON ct.categoryId = cc.id
            WHERE cc.lessonId =? and cc.stage='before'
            AND
            NOT EXISTS (
            SELECT *
            FROM  `course_task_result` ctr
            WHERE ctr.courseTaskId = ct.id
            and ctr.userId=?)
            ORDER BY ct.seq ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId, $userId)) ?: array();
    }

    public function getFirstNotStartedAfterTaskByLessonId($lessonId, $userId)
    {
        $sql = "SELECT ct.*
            FROM  `course_task` ct
            LEFT JOIN  `course_chapter` cc ON ct.categoryId = cc.id
            WHERE cc.lessonId =? and cc.stage='after'
            AND
            NOT EXISTS (
            SELECT *
            FROM  `course_task_result` ctr
            WHERE ctr.courseTaskId = ct.id
            and ctr.userId=?)
            ORDER BY ct.seq ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($lessonId, $userId)) ?: array();
    }

    public function findTasksByIdsAndTypes($taskIds, $types)
    {
        $marks1 = str_repeat('?,', count($taskIds) - 1).'?';
        $marks2 = str_repeat('?,', count($types) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE id IN ({$marks1}) AND type IN ({$marks2})";

        return $this->db()->fetchAll($sql, array_merge($taskIds, $types));
    }

    private function getInteractiveType()
    {
        return array(
            "brainStorm",
            "displayWall",
            "onesentence",
            "questionnaire",
            "testpaper",
            "practice",
        );
    }
}
