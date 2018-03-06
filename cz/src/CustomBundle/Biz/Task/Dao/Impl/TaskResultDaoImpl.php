<?php

namespace CustomBundle\Biz\Task\Dao\Impl;

use Biz\Task\Dao\TaskResultDao;
use Biz\Task\Dao\Impl\TaskResultDaoImpl as BaseTaskResultDaoImpl;

class TaskResultDaoImpl extends BaseTaskResultDaoImpl implements TaskResultDao
{
    public function findCourseIdsBetweenFromTimeAndToTime($from, $toTime)
    {
        $sql = "SELECT courseId FROM {$this->table} WHERE finishedTime between ? and ? GROUP BY courseId";

        return $this->db()->fetchAssoc($sql, array($from, $toTime)) ?: array();
    }

    public function countStudentsTasksByCourseIdAndStatus($courseId, $status)
    {
        $sql = "SELECT count(*) AS count,userId FROM {$this->table} m LEFT JOIN czie_course_lesson_task n ON m.courseTaskId = n.taskId WHERE (m.courseId = ? AND m.status = ? AND n.stage != 'in') GROUP BY userId";

        return $this->db()->fetchAll($sql, array($courseId, $status));
    }

    public function getByCourseIdAndTaskId($courseId, $taskId)
    {
        return $this->getByFields(array(
            'courseId' => $courseId,
            'courseTaskId' => $taskId
        ));
    }

    public function findByCourseIdAndUserIds($courseId, $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        $marks = $marks = str_repeat('?,', count($userIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE courseId = ? AND userId in ({$marks})";

        return $this->db()->fetchAll($sql, array_merge(array($courseId), $userIds));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'userId = :userId',
                'courseId = :courseId',
                'status = :status',
                'courseTaskId IN ( :courseTaskIds )',
            ),
        );
    }
}
