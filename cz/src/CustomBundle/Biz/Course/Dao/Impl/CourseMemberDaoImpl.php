<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\CourseMemberDao;
use Biz\Course\Dao\Impl\CourseMemberDaoImpl as BaseCourseMemberDaoImpl;

class CourseMemberDaoImpl extends BaseCourseMemberDaoImpl implements CourseMemberDao
{
    protected $table = 'course_member';

    public function findStudentCountsByCourseIds($courseIds)
    {
        $marks = str_repeat('?,', count($courseIds) - 1).'?';
        $sql   = "SELECT courseId, count(id) as count FROM {$this->table} WHERE courseId IN ({$marks}) AND role = 'student'";

        return $this->db()->fetchAll($sql, $courseIds);
    }

    public function countCourseSetTeachers($courseSetId, $userId)
    {
        $sql = "SELECT count(id) FROM {$this->table} WHERE courseSetId = ? AND userId = ? AND role = 'teacher'";

        return $this->db()->fetchColumn($sql, array($courseSetId, $userId));
    }

    public function randStudentByCourseId($courseId, $start, $exUserIds)
    {
        if (!empty($exUserIds)) {
            $marks = str_repeat('?,', count($exUserIds) - 1).'?';
            $sql = "SELECT * FROM {$this->table} WHERE `role` = 'student' AND `courseId` = ? AND `userId` NOT IN ({$marks}) LIMIT {$start},1;";

            return $this->db()->fetchAssoc($sql, array_merge(array($courseId), $exUserIds));
        }
        $sql = "SELECT * FROM {$this->table} WHERE `role` = 'student' AND `courseId` = ? LIMIT {$start},1;";

        return $this->db()->fetchAssoc($sql, array($courseId));
    }

    public function countTeachingCustomMembers($userId)
    {
        $sql = "SELECT count(cm.id) FROM {$this->table} cm LEFT JOIN course_set_v8 cs on cm.courseSetId = cs.id WHERE cs.type = 'instant' AND cm.userId = ? AND cm.role = 'teacher' AND cs.status = 'published' AND cs.courseNo is null";

        return $this->db()->fetchColumn($sql, array($userId));
    }

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function countLearningMembers($conditions)
    {
        $sql = "SELECT COUNT(m.id) FROM {$this->table()} m ";
        $sql .= 'INNER JOIN course_v8 c ON m.courseId = c.id ';
        $sql .= 'WHERE ';

        list($sql, $params) = $this->applySqlParams($conditions, $sql);

        $sql .= "c.type <> 'instant' AND (m.learnedNum < c.compulsoryTaskNum OR c.serializeMode = 'serialized')";

        return $this->db()->fetchColumn($sql, $params);
    }

    public function findLearningMembers($conditions, $start, $limit)
    {
        $sql = "SELECT m.* FROM {$this->table()} m ";
        $sql .= 'INNER JOIN course_v8 c ON m.courseId = c.id ';
        $sql .= 'WHERE ';

        list($sql, $params) = $this->applySqlParams($conditions, $sql);

        $sql .= "c.type <> 'instant' AND (m.learnedNum < c.compulsoryTaskNum OR c.serializeMode = 'serialized') ";
        $sql .= "ORDER BY createdTime DESC LIMIT {$start}, {$limit}";

        return $this->db()->fetchAll($sql, $params) ?: array();
    }

    public function findLearnedMembers($conditions, $start, $limit)
    {
        $sql = "SELECT m.* FROM {$this->table()} m ";
        $sql .= 'INNER JOIN course_v8 c ON m.courseId = c.id ';
        $sql .= 'WHERE ';
        list($sql, $params) = $this->applySqlParams($conditions, $sql);
        $sql .= "c.type <> 'instant' AND m.learnedNum >= c.compulsoryTaskNum  AND c.serializeMode IN ( 'none','finished') ";
        $sql .= "ORDER BY createdTime DESC LIMIT {$start}, {$limit}";

        return $this->db()->fetchAll($sql, $params) ?: array();
    }

    public function countLearnedMembers($conditions)
    {
        $sql = "SELECT COUNT(m.id) FROM {$this->table()} m ";
        $sql .= 'INNER JOIN course_v8 c ON m.courseId = c.id ';
        $sql .= 'WHERE ';

        list($sql, $params) = $this->applySqlParams($conditions, $sql);
        $sql .= "c.type <> 'instant' AND m.learnedNum >= c.compulsoryTaskNum  AND c.serializeMode IN ( 'none','finished') ";

        return $this->db()->fetchColumn($sql, $params);
    }

    public function findStudentWithScore($courseId, $start, $limit)
    {
        $sql = "SELECT cm.*, t.scores, u.nickname from course_member cm left join ";
        $sql .= "user u on cm.userId = u.id left join (SELECT s.userId, sum(s.score) as scores FROM `czie_user_score` s  WHERE s.courseId = ? group by s.userId) ";
        $sql .= "t on cm.userId = t.userId WHERE cm.courseId = ? AND cm.role = 'student' order by t.scores DESC, u.nickname ASC ";
        $sql .= "LIMIT {$start}, {$limit}";

        return $this->db()->fetchAll($sql, array($courseId, $courseId));
    }

    public function findInstantCourseIdByTeacherId($teacherId)
    {
        $sql = "SELECT courseId FROM {$this->table} WHERE userId LIKE ? AND role = 'teacher'";

        return $this->db()->fetchAll($sql, $teacherId);
    }

    public function deleteByCourseMemberId($courseMemberId)
    {
        return $this->db()->delete($this->table(), array('courseMemberId' => $courseMemberId));
    }

    public function findCurrentTermTeacherMembersByUserId($userId, $termCode)
    {
        $sql = "SELECT * FROM {$this->table} m LEFT JOIN course_v8 c on m.courseId = c.id WHERE m.userId = ? AND c.termCode = ? AND m.role = 'teacher'";

        return $this->db()->fetchAll($sql, array($userId, $termCode));
    }

    public function searchExportMembers($conditions, $start, $limit)
    {
        $sql = "SELECT m.* FROM {$this->table} m LEFT JOIN user u on m.userId = u.id WHERE m.courseId = ? AND m.role = ? ORDER BY u.nickname ASC LIMIT {$start}, {$limit} ";
        return $this->db()->fetchAll($sql, array($conditions['courseId'], $conditions['role']));
    }

    public function findTeacherCourseIds()
    {
        $sql = "select min(id) id from `course_member` WHERE role = 'teacher' group by courseId";

        return $this->db()->fetchAll($sql);
    }

    public function findAllCourseMasterTeachersByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }

        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "SELECT userId AS teacherId, courseId FROM `course_member` WHERE id IN ({$marks})";

        return $this->db()->fetchAll($sql, $ids);
    }

    public function findAllCourseMasterTeachers()
    {
        $sql = "SELECT userId AS teacherId, courseId FROM `course_member` WHERE id IN (select min(id) from `course_member` WHERE role = 'teacher' group by courseId)";

        return $this->db()->fetchAll($sql);
    }

    public function findByUserIdsAndRole($userIds, $role)
    {
        if (empty($userIds) || empty($role)) {
            return array();
        }
        $marks = str_repeat('?,', count($userIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `role` = ? AND `userId` IN ({$marks});";

        return $this->db()->fetchAll($sql, array_merge(array($role), $userIds));
    }

    public function declares()
    {
        return array(
            'timestamps' => array('createdTime', 'updatedTime'),
            'orderbys' => array(
                'createdTime',
                'lastLearnTime',
                'classroomId',
                'id',
                'updatedTime',
                'lastViewTime',
                'seq',
            ),
            'conditions' => array(
                'userId = :userId',
                'courseId = :courseId',
                'isLearned = :isLearned',
                'joinedType = :joinedType',
                'role = :role',
                'isVisible = :isVisible',
                'classroomId = :classroomId',
                'noteNum > :noteNumGreaterThan',
                'createdTime >= :startTimeGreaterThan',
                'createdTime < :startTimeLessThan',
                'courseId IN (:courseIds)',
                'userId IN (:userIds)',
                'learnedNum >= :learnedNumGreaterThan',
                'learnedNum < :learnedNumLessThan',
                'deadline >= :deadlineGreaterThan',
                'lastViewTime >= lastViewTime_GE',
                'lastLearnTime >= :lastLearnTimeGreaterThan',
                'userId NOT IN (:exUserIds)',
            ),
        );
    }
}
