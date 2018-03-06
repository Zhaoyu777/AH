<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\CourseDao;
use Biz\Course\Dao\Impl\CourseDaoImpl as BaseCourseDaoImpl;

class CourseDaoImpl extends BaseCourseDaoImpl implements CourseDao
{
    public function findInstantCoursesByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }

        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE type = 'instant' AND status <> 'delete' AND id IN ({$marks}) ORDER BY createdTime DESC;";

        return $this->db()->fetchAll($sql, $ids);
    }

    public function findInstantCoursesByIdsAndTermCode($ids, $termCode)
    {
        if (empty($ids)) {
            return array();
        }

        $value = $ids;
        $value[] = $termCode;
        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE type = 'instant' AND status <> 'delete' AND id IN ({$marks}) AND termCode = ? ORDER BY createdTime DESC;";

        return $this->db()->fetchAll($sql, $value);
    }

    public function findInstantCoursesByTermCode($termCode)
    {
        $sql = "SELECT * FROM {$this->table} WHERE type = 'instant' AND status <> 'delete' AND  termCode = ? ORDER BY createdTime DESC;";

        return $this->db()->fetchAll($sql, array($termCode));
    }

    public function findSchoolCoursesByIdsAndTermCode($ids, $termCode)
    {
        if (empty($ids)) {
            return array();
        }

        $value = $ids;
        $value[] = $termCode;
        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "SELECT c.* FROM {$this->table} c LEFT JOIN `course_set_v8` cs ON c.courseSetId = cs.id WHERE courseNo is not null AND c.type = 'instant' AND c.status <> 'delete' AND c.id IN ({$marks}) AND termCode = ?;";

        return $this->db()->fetchAll($sql, $value);
    }

    public function findNotClosedCourseCountsBySetIdsAndTeacherId($courseSetIds, $teacherId)
    {
        $value   = $courseSetIds;
        $value[] = "%|{$teacherId}|%";
        $marks   = str_repeat('?,', count($courseSetIds) - 1).'?';
        $sql     = "SELECT courseSetId, count(id) as count FROM {$this->table} WHERE status <> 'delete' AND courseSetId IN ({$marks}) AND teacherIds LIKE ? GROUP BY courseSetId;";

        return $this->db()->fetchAll($sql, $value);
    }

    public function findNotClosedCoursesByTeacherId($teacherId)
    {
        $teacherId = "%|{$teacherId}|%";
        $sql       = "SELECT * FROM {$this->table} WHERE teacherIds LIKE ? AND status <> 'delete'";

        return $this->db()->fetchAll($sql, array($teacherId));
    }

    public function findNotClosedCoursesByTeacherIdAndTermCode($teacherId, $termCode)
    {
        $teacherId = "%|{$teacherId}|%";
        $sql       = "SELECT * FROM {$this->table} WHERE teacherIds LIKE ? AND termCode = ? AND status <> 'delete'";

        return $this->db()->fetchAll($sql, array($teacherId, $termCode));
    }

    public function findNormalCoursesByIds($ids)
    {
        $value = $ids;
        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE type != 'instant' AND status = 'published' AND id IN ({$marks}) ORDER BY createdTime DESC;";

        return $this->db()->fetchAll($sql, $value);
    }

    public function findInstantCourseIdByTeacherId($teacherId)
    {
        $teacherId = "%|{$teacherId}|%";
        $sql       = "SELECT id FROM {$this->table} WHERE teacherIds LIKE ? AND status <> 'delete' AND type = 'instant'";

        return $this->db()->fetchAll($sql, array($teacherId));
    }

    public function countInstantCourseByUserIdsAndTermCodeAndRoleGroupUserId($userIds, $termCode, $role)
    {
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT count(c.id) as count, cm.userId FROM {$this->table} c ";
        $sql .= "LEFT JOIN `course_member` cm ON c.id = cm.courseId ";
        $sql .= "WHERE c.type = 'instant' AND c.status <> 'delete' AND cm.role = ? AND cm.userId IN ({$marks}) AND c.termCode = ? GROUP BY cm.userId";

        $fields = array_merge(array($role), $userIds, array($termCode));

        return $this->db()->fetchAll($sql, $fields) ? : array();
    }

    public function findInstantCoursesByUserIdAndTermCodeAndRole($userId, $termCode, $role)
    {
        $sql = "SELECT c.* FROM {$this->table} c ";
        $sql .= "LEFT JOIN `course_member` cm ON c.id = cm.courseId ";
        $sql .= "WHERE c.type = 'instant' AND c.status <> 'delete' AND cm.role = ? AND cm.userId = ? AND c.termCode = ?";

        return $this->db()->fetchAll($sql, array($role, $userId, $termCode)) ? : array();
    }

    public function declares()
    {
        return array(
            'serializes' => array(
                'goals' => 'delimiter',
                'audiences' => 'delimiter',
                'services' => 'delimiter',
                'teacherIds' => 'delimiter',
            ),
            'orderbys' => array(
                'hitNum',
                'recommendedTime',
                'rating',
                'studentNum',
                'recommendedSeq',
                'createdTime',
                'originPrice',
                'updatedTime',
                'courseSetId',
            ),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'courseSetId = :courseSetId',
                'updatedTime >= :updatedTime_GE',
                'status = :status',
                'type = :type',
                'type <> :excludeType',
                'price = :price',
                'price > :price_GT',
                'originPrice > :originPrice_GT',
                'originPrice = :originPrice',
                'coinPrice > :coinPrice_GT',
                'coinPrice = :coinPrice',
                'originCoinPrice > :originCoinPrice_GT',
                'originCoinPrice = :originCoinPrice',
                'title LIKE :titleLike',
                'userId = :userId',
                'recommended = :recommended',
                'createdTime >= :startTime',
                'createdTime < :endTime',
                'rating > :ratingGreaterThan',
                'vipLevelId >= :vipLevelIdGreaterThan',
                'vipLevelId = :vipLevelId',
                'categoryId = :categoryId',
                'smallPicture = :smallPicture',
                'categoryId IN ( :categoryIds )',
                'vipLevelId IN ( :vipLevelIds )',
                'parentId = :parentId',
                'parentId > :parentId_GT',
                'parentId IN ( :parentIds )',
                'id NOT IN ( :excludeIds )',
                'id IN ( :courseIds )',
                'locked = :locked',
                'lessonNum > :lessonNumGT',
                'orgCode = :orgCode',
                'orgCode LIKE :likeOrgCode',
                'teacherIds LIKE :teacherId',
                'termCode = :termCode',
            ),
        );
    }
}
