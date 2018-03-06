<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\CourseLessonDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class CourseLessonDaoImpl extends GeneralDaoImpl implements CourseLessonDao
{
    protected $table = 'czie_course_lesson';

    public function getCurrenTeachCourseLesson($courseId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `courseId` = ? AND `status` <> 'teached' ORDER BY `number` ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($courseId)) ?: array();
    }

    public function getLastTeachedCourseLesson($courseId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `courseId` = ? AND `status` = 'teached' ORDER BY `number` DESC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($courseId)) ?: array();
    }

    public function getNextTeachCourseLesson($courseId, $number)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `courseId` = ? AND`number` > ? AND `status` <> 'teached' ORDER BY `number` ASC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($courseId, $number)) ?: array();
    }

    public function getByCourseIdAndNumber($courseId, $number)
    {
        return $this->getByFields(array(
            'courseId' => $courseId,
            'number' => $number,
        ));
    }

    public function getTeachingByCourseId($courseId)
    {
        return $this->getByFields(array(
            'courseId' => $courseId,
            'status' => 'teaching',
        ));
    }

    public function findAllLessons()
    {
        $sql = "SELECT * FROM {$this->table}";

        return $this->db()->fetchAll($sql);
    }

    public function findByCourseIds($courseIds)
    {
        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE `courseId` IN ({$marks})";

        return $this->db()->fetchAll($sql, $courseIds) ?: array();
    }

    public function findByCourseId($courseId)
    {
        return $this->findByFields(array('courseId' => $courseId));
    }

    public function findByCourseIdAndStatus($courseId, $status)
    {
        return $this->findByFields(array('courseId' => $courseId, 'status' => $status));
    }

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function findCourseLessonCountByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT courseId, COUNT(id) as count FROM {$this->table} WHERE `courseId` IN ({$marks}) GROUP BY courseId";

        return $this->db()->fetchAll($sql, $courseIds) ?: array();
    }

    public function countByOrgCodeAndTimeRange($orgCode, $startTime, $endTime)
    {
        $sql = "SELECT count(cl.id) FROM {$this->table} cl LEFT JOIN `czie_api_course` ac ON cl.courseId = ac.courseId LEFT JOIN `user` u ON ac.jsdm = u.nickname WHERE u.orgCode LIKE ? and cl.status = 'teached' and cl.startTime > ? AND cl.endTime < ?";

        return $this->db()->fetchColumn($sql, array("{$orgCode}%", $startTime, $endTime)) ?: 0;
    }

    public function countSchoolTasksByTermCode($termCode)
    {
        $sql = "SELECT count(cl.id) FROM {$this->table} cl LEFT JOIN `course_v8` c ON cl.courseId = c.id LEFT JOIN `course_set_v8` cs ON c.courseSetId = cs.id WHERE cl.status = 'teached' AND cs.orgCode LIKE '1.%' AND c.termCode = ?";

        return $this->db()->fetchColumn($sql, array($termCode)) ?: 0;
    }

    public function findCountLessonByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT courseId, COUNT(id) as count FROM {$this->table} WHERE `courseId` IN ({$marks}) GROUP BY courseId";

        return $this->db()->fetchAll($sql, $courseIds) ?: array();
    }

    public function findCountLessonByCourseIdsAndStatus($courseIds, $status)
    {
        if (empty($courseIds)) {
            return array();
        }

        $marks = str_repeat('?,', count($courseIds) - 1).'?';

        $sql = "SELECT courseId, COUNT(id) as count FROM {$this->table} WHERE `courseId` IN ({$marks}) AND `status` = ? GROUP BY courseId";

        return $this->db()->fetchAll($sql, array_merge($courseIds, array($status))) ?: array();
    }

    public function findTeachedByTime($startTime, $endTime)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `endTime`>= ? AND `endTime`<= ? AND status = 'teached'";

        return $this->db()->fetchAll($sql, array($startTime, $endTime));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array('id', 'startTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'courseId = :courseId',
                'status = :status',
                'courseId IN ( :courseIds )',
                'taskNum > :gtTaskNum'
            ),
        );
    }
}
