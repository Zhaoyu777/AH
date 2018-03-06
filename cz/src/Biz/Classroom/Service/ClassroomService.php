<?php

namespace Biz\Classroom\Service;

interface ClassroomService
{
    public function searchMembers($conditions, $orderBy, $start, $limit);

    public function findClassroomsByIds(array $ids);

    public function findActiveCoursesByClassroomId($classroomId);

    // TODO refactor.
    public function findMembersByUserIdAndClassroomIds($userId, $classroomIds);

    public function getClassroom($id);

    public function updateClassroom($id, $fields);

    public function batchUpdateOrg($classroomIds, $orgCode);

    public function waveClassroom($id, $field, $diff);

    public function findAssistants($classroomId);

    public function findTeachers($classroomId);

    public function canManageClassroom($id);

    public function tryManageClassroom($id, $actionPermission = null);

    public function canCreateThreadEvent($resource);

    public function addCoursesToClassroom($classroomId, $courseIds);

    /**
     * 是否可参与班级的活动，只有正式学员、教师、网站管理员才能参与班级的活动，旁听生不能参与活动.
     */
    public function canTakeClassroom($id, $includeAuditor = false);

    public function tryTakeClassroom($id, $includeAuditor = false);

    /**
     * 是否可处理班级事务（批改作业，试卷等）.
     */
    public function canHandleClassroom($id);

    public function tryHandleClassroom($id);

    /**
     * 是否可查看班级，　所有班级成员、网站管理员都可以查看.
     */
    public function canLookClassroom($id);

    public function tryLookClassroom($id);

    public function canJoinClassroom($id);

    public function canLearnClassroom($id);

    public function deleteClassroom($id);

    public function searchClassrooms($conditions, $orderBy, $start, $limit);

    public function countClassrooms($condtions);

    public function addClassroom($classroom);

    public function findClassroomByTitle($title);

    public function findClassroomsByLikeTitle($title);

    public function closeClassroom($id);

    public function publishClassroom($id);

    /**
     * 班级课程API.
     */
    public function updateClassroomCourses($classroomId, $activeCourseIds);

    public function findClassroomsByCoursesIds($courseIds);

    public function findClassroomsByCourseSetIds(array $courseSetIds);

    public function findClassroomCourseByCourseSetIds($courseSetIds);

    /**
     * @before findClassroomByCourseId
     *
     * @param  $courseId
     *
     * @return mixed
     */
    public function getClassroomByCourseId($courseId);

    public function getClassroomCourseByCourseSetId($courseSetId);

    // 内部方法
    public function updateClassroomTeachers($id);

    public function changePicture($id, $data);

    public function isCourseInClassroom($courseId, $classroomId);

    public function deleteClassroomCourses($classroomId, array $courseIds);

    public function isClassroomStudent($classroomId, $studentId);

    public function isClassroomAuditor($classroomId, $studentId);

    public function isClassroomAssistant($classroomId, $userId);

    public function isClassroomHeadTeacher($classroomId, $userId);

    public function updateMember($id, $member);

    public function searchMemberCount($conditions);

    public function findMemberUserIdsByClassroomId($classroomId);

    public function getClassroomMember($classroomId, $userId);

    public function remarkStudent($classroomId, $userId, $remark);

    public function removeStudent($classroomId, $userId);

    public function becomeStudent($classroomId, $userId, $info = array());

    public function becomeStudentWithOrder($classroomId, $userId, $info = array());

    public function becomeAuditor($classroomId, $userId);

    public function becomeAssistant($classroomId, $userId);

    public function addHeadTeacher($classroomId, $userId);

    public function updateAssistants($classroomId, $userIds);

    public function isClassroomTeacher($classroomId, $userId);

    public function findClassroomIdsByCourseId($courseId);

    public function findClassroomsByCourseId($courseId);

    /**
     * @before findClassroomCourse
     *
     * @param  $classroomId
     * @param  $courseId
     *
     * @return mixed
     */
    public function getClassroomCourse($classroomId, $courseId);

    public function findCoursesByClassroomId($classroomId);

    public function findClassroomStudents($classroomId, $start, $limit);

    public function findClassroomMembersByRole($classroomId, $role, $start, $limit);

    public function lockStudent($classroomId, $userId);

    public function unlockStudent($classroomId, $userId);

    public function recommendClassroom($id, $number);

    public function cancelRecommendClassroom($id);

    public function tryAdminClassroom($classroomId);

    public function getClassroomMembersByCourseId($courseId, $userId);

    public function findUserJoinedClassroomIds($userId);

    public function updateLearndNumByClassroomIdAndUserId($classroomId, $userId);

    public function countCoursesByClassroomId($classroomId);

    public function countMobileFilledMembersByClassroomId($classroomId, $locked = 0);

    public function isClassroomOverDue($classroom);

    public function updateMemberDeadlineByMemberId($memberId, $deadline);

    public function updateMembersDeadlineByClassroomId($classroomId, $deadline);

    public function findWillOverdueClassrooms();

    public function countCourseTasksByClassroomId($classroomId);

    public function findUserPaidCoursesInClassroom($userId, $classroomId);

    public function findMembersByMemberIds($ids);

    public function tryFreeJoin($classroomId);
}
