<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\MemberService;
use CustomBundle\Biz\Course\Service\CourseLessonService;
use CustomBundle\Biz\SignIn\Service\SignInService;
use Biz\Course\Service\Impl\MemberServiceImpl as BaseMemberServiceImpl;

class MemberServiceImpl extends BaseMemberServiceImpl implements MemberService
{
    public function batchBecomeStudents($courseId, $studentIds)
    {
        if (empty($studentIds)) {
            return array();
        }

        foreach ($studentIds as $studentId) {
            $this->becomeStudent($courseId, $studentId);
        }
    }

    public function findCurrentTermTeacherMembersByUserId($userId, $termCode)
    {
        return $this->getMemberDao()->findCurrentTermTeacherMembersByUserId($userId, $termCode);
    }

    public function countCourseSetTeachers($courseSetId, $userId)
    {
        return $this->getMemberDao()->countCourseSetTeachers($courseSetId, $userId);
    }

    public function findMembersByIdsWithUserInfo($ids, $withUserInfo = false)
    {
        $ids = array_values($ids);
        $members =  ArrayToolkit::index($this->getMemberDao()->findByIds($ids), 'id');
        if ($withUserInfo) {
            $userIds = ArrayToolkit::column($members, 'userId');
            $users = $this->getUserService()->findUsersByIds($userIds);
            $userProfiles = $this->getUserService()->findUserProfilesByIds($userIds);

            $apiCourseMembers = $this->findApiMembersByMemberIds($ids);
            $apiCourseMemberIds = ArrayToolkit::column($apiCourseMembers, 'memberId');

            foreach ($members as &$member) {
                $member['truename'] = $userProfiles[$member['userId']]['truename'];
                $member['nickname'] = $users[$member['userId']]['nickname'];
                $member['number'] = $users[$member['userId']]['number'];
                $member['email'] = $users[$member['userId']]['email'];
                $member['roles'] = $users[$member['userId']]['roles'];
                $member['from'] = in_array($member['id'], $apiCourseMemberIds) ? 'import' : 'add';
            }
        }

        return $members;
    }

    public function findCourseStudentsWithUserInfo($courseId)
    {
        $students = $this->getMemberDao()->findByCourseIdAndRole($courseId, 'student');

        $userIds = ArrayToolkit::column($students, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        array_walk(
            $students,
            function (&$student) use ($users) {
                $student['truename'] = $users[$student['userId']]['truename'];
                $student['nickname'] = $users[$student['userId']]['nickname'];
                $student['number'] = $users[$student['userId']]['number'];
                $student['email'] = $users[$student['userId']]['email'];
                $student['roles'] = $users[$student['userId']]['roles'];
                $student['avatar'] = $users[$student['userId']]['smallAvatar'];
            }
        );

        return ArrayToolkit::index($students, 'userId');
    }

    public function countTeachingCustomMembers($userId)
    {
        return $this->getMemberDao()->countTeachingCustomMembers($userId);
    }

    public function findRandomStudentIdsByLessonId($lessonId, $clearUserIds = array(), $count = 1)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $signIn = $this->getSignInService()->findSignInsByLessonId($lessonId);
        $signInCount = count($signIn);

        if ($signInCount < 1) {
            $students = $this->findByCourseIdAndRole($lesson['courseId'], 'student');
        } elseif ($signInCount == 1) {
            $signInOne = $this->getSignInService()->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 1, 'attend');
            $students = ArrayToolkit::index($signInOne, 'userId');
        } elseif ($signInCount == 2) {
            $signInTwo = $this->getSignInService()->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 2, 'attend');
            $students = ArrayToolkit::index($signInTwo, 'userId');
        }

        $userIds = ArrayToolkit::column($students, 'userId');
        $userIds = array_diff($userIds, $clearUserIds);
        if (empty($userIds)) {
            return array();
        }

        if (count($userIds) > $count) {
            $userIds = array_flip($userIds);
            $userIds = array_rand($userIds, $count);
        }
        if (!is_array($userIds)) {
            $userIds = array($userIds);
        }

        return array_values($userIds);
    }

    public function fintStudentsByCourseIdWithSocre($courseId, $start, $limit)
    {
        return $this->getMemberDao()->findStudentWithScore($courseId, $start, $limit);
    }

    public function randStudentByCourseId($courseId, $rand, $exUserIds = array())
    {
        return $this->getMemberDao()->randStudentByCourseId($courseId, $rand, $exUserIds);
    }

    public function searchExportMembers($conditions, $start, $limit)
    {
        return $this->getMemberDao()->searchExportMembers($conditions, $start, $limit);
    }

    public function findApiMembersByMemberIds($memberIds)
    {
        return $this->getApiMemberDao()->findByMemberIds($memberIds);
    }

    public function findByCourseIdAndRole($courseId, $role)
    {
        return $this->getMemberDao()->findByCourseIdAndRole($courseId, 'student');
    }

    public function removeCourseStudent($courseId, $userId)
    {
        $this->getCourseService()->tryManageCourse($courseId);
        $user = $this->getUserService()->getUser($userId);
        if (empty($user)) {
            throw $this->createNotFoundException("User#{$user['id']} Not Found");
        }
        $member = $this->getMemberDao()->getByCourseIdAndUserId($courseId, $userId);
        if (empty($member)) {
            throw $this->createNotFoundException("User#{$user['id']} Not in Course#{$courseId}");
        }
        if ($member['role'] !== 'student') {
            throw $this->createInvalidArgumentException("User#{$user['id']} is Not a Student of Course#{$courseId}");
        }

        $this->beginTransaction();
        try {
            $result = $this->getMemberDao()->delete($member['id']);

            $course = $this->getCourseService()->getCourse($courseId);

            $this->getLogService()->info(
                'course',
                'remove_student',
                "教学计划《{$course['title']}》(#{$course['id']})，移除学员{$user['nickname']}(#{$user['id']})"
            );

            $this->dispatchEvent('course.quit', $course, array('userId' => $userId, 'member' => $member));
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        if ($this->getCurrentUser()->isAdmin()) {
            $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
            $this->getNotificationService()->notify(
                $member['userId'],
                'student-remove',
                array(
                    'courseId' => $course['id'],
                    'courseTitle' => $courseSet['title'],
                )
            );
        }

        return $result;
    }

    public function changeCourseMainTeacher($courseId, $teacher)
    {
        $data = $this->getCourseMainTeacherDao()->getByCourseId($courseId);

        if (!empty($data)) {
            $this->getCourseMainTeacherDao()->deleteByCourseId($courseId);
        }

        return $this->getCourseMainTeacherDao()->create(array(
            'courseId' => $courseId,
            'teacherId' => $teacher['id'],
        ));
    }

    public function findTeacherMembersByUserIds($userIds)
    {
        return $this->getMemberDao()->findByUserIdsAndRole($userIds, 'teacher');
    }

    // 方法慎用
    public function batchCreate($teachers)
    {
        if (empty($teachers)) {
            return array();
        }

        return $this->getCourseMainTeacherDao()->batchCreate($teachers);
    }

    public function findAllMainTeachers()
    {
        return $this->getCourseMainTeacherDao()->findAllMainTeachers();
    }

    public function findMainTeachersByCourseIds($courseIds)
    {
        return $this->getCourseMainTeacherDao()->findMainTeachersByCourseIds($courseIds);
    }

    public function findCoursesByTeacherId($teacherId)
    {
        return $this->getCourseMainTeacherDao()->findCoursesByTeacherId($teacherId);
    }

    public function findTeachersByCourseIds($courseIds)
    {
         return $this->getCourseMainTeacherDao()->findTeachersByCourseIds($courseIds);
    }

    protected function getCourseMainTeacherDao()
    {
        return $this->createDao('CustomBundle:Course:CourseMainTeacherDao');
    }

    protected function getMemberDao()
    {
        return $this->createDao('CustomBundle:Course:CourseMemberDao');
    }

    protected function getApiMemberDao()
    {
        return $this->createDao('CustomBundle:Course:ApiCourseMemberDao');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    private function getNotificationService()
    {
        return $this->createService('User:NotificationService');
    }
}
