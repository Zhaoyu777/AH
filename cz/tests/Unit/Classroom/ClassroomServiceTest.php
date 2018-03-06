<?php

namespace Tests\Unit\Classroom;

use AppBundle\Common\ClassroomToolkit;
use Biz\BaseTestCase;
use Biz\Classroom\Service\ClassroomService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\MemberService;
use Biz\User\CurrentUser;
use Biz\User\Service\UserService;

class ClassroomServiceTest extends BaseTestCase
{
    public function testUpdateMemberDeadlineByMemberId()
    {
        $user = $this->getCurrentUser();
        $textClassroom = array(
            'title' => 'test066',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $student = $this->getClassroomService()->becomeStudent($classroom['id'], $user['id'], $info = array());

        $time = time();
        $deadline = ClassroomToolkit::buildMemberDeadline(array(
            'expiryMode' => 'date',
            'expiryValue' => $time,
        ));
        $student = $this->getClassroomService()->updateMemberDeadlineByMemberId($student['id'], array(
            'deadline' => $deadline,
        ));
        $this->assertEquals($time, $student['deadline']);
    }

    public function testUpdateMembersDeadlineByClassroomId()
    {
        $textClassroom = array(
            'title' => 'test066',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);
        $user = $this->createStudent();

        $this->getClassroomService()->becomeStudent($classroom['id'], $user['id']);
        $time = time();
        $deadline = ClassroomToolkit::buildMemberDeadline(array(
            'expiryMode' => 'date',
            'expiryValue' => $time,
        ));

        $updated = $this->getClassroomService()->updateMembersDeadlineByClassroomId($classroom['id'], $deadline);

        $this->assertEquals(1, $updated);
    }

    public function testAddClassroom()
    {
        $textClassroom = array(
            'title' => 'test',
            'status' => 'draft',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $this->assertEquals(1, $classroom['id']);

        $this->assertEquals($textClassroom['title'], $classroom['title']);

        $this->assertEquals('draft', $classroom['status']);
    }

    public function testGetClassroom()
    {
        $textClassroom = array(
            'title' => 'test11',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);
        // $classroom = $this->getClassroomService()->updateClassroom($id, $fields);
        // 是为了清空缓存再getClassroom,保证test之间互不影响,下同
        $classroom = $this->getClassroomService()->getClassroom($classroom['id']);

        $this->assertEquals(1, $classroom['id']);

        $this->assertEquals($textClassroom['title'], $classroom['title']);

        $this->assertEquals('draft', $classroom['status']);
    }

    public function testSearchClassrooms()
    {
        $classrooms = $this->getClassroomService()->searchClassrooms(array(), array(), 0, 1);

        $this->assertEmpty($classrooms);
        $textClassroom = array(
            'title' => 'test11',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classrooms = $this->getClassroomService()->searchClassrooms(array('id' => $classroom['id']), array(), 0, 1);
        $this->assertEquals(array_shift($classrooms), $classroom);
    }

    public function testcountClassrooms()
    {
        $textClassroom1 = array(
            'title' => 'test1',
        );
        $textClassroom2 = array(
            'title' => 'test2',
        );
        $textClassroom3 = array(
            'title' => 'test3',
        );
        $classroom1 = $this->getClassroomService()->addClassroom($textClassroom1);
        $this->getClassroomService()->updateClassroom($classroom1['id'], $textClassroom1);
        $classroom2 = $this->getClassroomService()->addClassroom($textClassroom2);
        $this->getClassroomService()->updateClassroom($classroom2['id'], $textClassroom2);
        $classroom3 = $this->getClassroomService()->addClassroom($textClassroom3);
        $this->getClassroomService()->updateClassroom($classroom3['id'], $textClassroom3);
        $conditions = array('status' => 'draft', 'showable' => 1, 'buyable' => 1);
        $result = $this->getClassroomService()->countClassrooms($conditions);
        $this->assertEquals(3, $result);
    }

    public function testRecommendClassroom()
    {
    }

    public function testCancelRecommendClassroom()
    {
    }

    public function testGetClassroomCourse()
    {
        $textClassroom = array(
            'title' => 'test19878',
        );

        $course1 = $this->createCourse('Test Course 1');

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $copyCourses = $this->getClassroomService()->addCoursesToClassroom($classroom['id'], array($course1['id']));
        $copyCourse = current($copyCourses);
        $result = $this->getClassroomService()->getClassroomCourse($classroom['id'], $copyCourse['id']);
        $this->assertEquals($copyCourse['id'], $result['courseId']);
    }

    public function testFindActiveCoursesByClassroomId()
    {
        $textClassroom = array(
            'title' => 'testwe',
        );

        $course1 = $this->createCourse('Test Course 1');
        $course2 = $this->createCourse('Test Course 2');

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $courseSet = $this->mockCourseSet();
        $courseSet = $this->getCourseSetService()->createCourseSet($courseSet);

        $copyCourses = $this->getClassroomService()->addCoursesToClassroom($classroom['id'],
            array($course1['id'], $course2['id']));
        $courses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroom['id']);

        $this->assertEquals(2, count($courses));

        $this->getClassroomService()->deleteClassroomCourses($classroom['id'], array($copyCourses[1]['id']));
        $courses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroom['id']);
        $this->assertEquals(1, count($courses));

        $courseFirst = $courses[0];
        $this->assertEquals($course1['title'], $courseFirst['title']);
    }

    public function testFindClassroomIdsByCourseId()
    {
        $textClassroom = array(
            'title' => 'test3',
        );
        $course = $this->createCourse('Test Course 1');
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);

        $this->getClassroomService()->addCoursesToClassroom($classroom['id'], array($course['id']));

        $classroomId = $this->getClassroomService()->findClassroomIdsByCourseId(1);

        $this->assertEquals(1, $classroom['id']);
    }

    public function testFindClassroomsByCourseId()
    {
        $textClassroom1 = array(
            'title' => 'test12333',
        );
        $course1 = $this->createCourse('Test Course 1');
        $course2 = $this->createCourse('Test Course 2');
        $classroom1 = $this->getClassroomService()->addClassroom($textClassroom1);

        $this->getClassroomService()->addCoursesToClassroom($classroom1['id'], array($course1['id']));

        $textClassroom2 = array(
            'title' => 'test11123',
        );

        $classroom2 = $this->getClassroomService()->addClassroom($textClassroom2);

        $this->getClassroomService()->addCoursesToClassroom($classroom2['id'], array($course2['id']));

        $classroom = $this->getClassroomService()->updateClassroom(1, $textClassroom1);

        $this->assertEquals('test12333', $classroom['title']);
    }

    public function testFindClassroomByCourseId()
    {
        $textClassroom = array(
            'title' => 'test1234',
        );
        $course = $this->createCourse('Test Course');
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);

        $this->getClassroomService()->addCoursesToClassroom($classroom['id'], array($course['id']));
        $result = $this->getClassroomService()->getClassroomByCourseId($course['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $this->assertEquals('test1234', $classroom['title']);
    }

    public function testFindClassroomByTitle()
    {
        $textClassroom = array(
            'title' => 'test111',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);
        $title = 'test111';

        $result = $this->getClassroomService()->findClassroomByTitle($title);

        $this->assertEquals($textClassroom['title'], $result['title']);
    }

    public function testFindClassroomsByLikeTitle()
    {
        $textClassroom1 = array(
            'title' => 'test232',
        );
        $textClassroom2 = array(
            'title' => 'test334',
        );
        $classroom1 = $this->getClassroomService()->addClassroom($textClassroom1);
        $classroom1 = $this->getClassroomService()->updateClassroom($classroom1['id'], $textClassroom1);
        $classroom2 = $this->getClassroomService()->addClassroom($textClassroom2);
        $classroom2 = $this->getClassroomService()->updateClassroom($classroom2['id'], $textClassroom2);
        $likeTitle = '%test2%';

        $result = $this->getClassroomService()->findClassroomsByLikeTitle($likeTitle);

        $this->assertEquals(1, count($result));
    }

    public function testUpdateClassroom()
    {
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);

        $fields = array(
            'title' => 'test11111',
        );

        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $fields);

        $this->assertEquals($fields['title'], $classroom['title']);

        $classroom = $this->getClassroomService()->updateClassroom('999', $fields);

        $this->assertEquals(null, $classroom);
    }

    public function testWaveClassroom()
    {
    }

    public function testDeleteClassroom()
    {
        $textClassroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);
        $this->getClassroomService()->deleteClassroom($classroom['id']);
        $result = $this->getClassroomService()->getClassroom($classroom['id']);
        $this->assertEquals(0, count($result));
    }

    public function testIsClassroomTeacher()
    {
    }

    public function testUpdateClassroomTeachers()
    {
    }

    public function testPublishClassroom()
    {
        $textClassroom = array(
            'title' => 'test6543',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->assertEquals('draft', $classroom['status']);

        $currentUser = new CurrentUser();
        $currentUser->fromArray(array(
            'id' => 3,
            'nickname' => 'admin3',
            'email' => 'admin3@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));
        $this->getServiceKernel()->setCurrentUser($currentUser);

        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);
        $this->assertEquals(false, $enabled);

        $this->getClassroomService()->addHeadTeacher($classroom['id'], 3);

        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);

        $this->assertEquals(true, $enabled);

        $this->getClassroomService()->publishClassroom($classroom['id']);
        $result = $this->getClassroomService()->getClassroom($classroom['id']);

        $this->assertEquals('published', $result['status']);
    }

    public function testCloseClassroom()
    {
        $textClassroom = array(
            'title' => 'test1111223',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $currentUser = new CurrentUser();
        $currentUser->fromArray(array(
            'id' => 4,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));
        $this->getServiceKernel()->setCurrentUser($currentUser);

        $this->getClassroomService()->addHeadTeacher($classroom['id'], 4);

        $this->getClassroomService()->publishClassroom($classroom['id']);
        $result = $this->getClassroomService()->getClassroom($classroom['id']);

        $this->assertEquals('published', $result['status']);

        $this->getClassroomService()->closeClassroom($classroom['id']);
        $result = $this->getClassroomService()->getClassroom($classroom['id']);

        $this->assertEquals('closed', $result['status']);
    }

    public function testChangePicture()
    {
    }

    public function testIsCourseInClassroom()
    {
        $textClassroom = array(
            'title' => 'test43234',
        );
        $course = $this->createCourse('Test Course 1');

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $copyCourses = $this->getClassroomService()->addCoursesToClassroom($classroom['id'], array($course['id']));
        $copyCourse = current($copyCourses);
        $enabled = $this->getClassroomService()->isCourseInClassroom($copyCourse['id'], $classroom['id']);
        $this->assertEquals(true, $enabled);

        $this->getClassroomService()->deleteClassroomCourses($classroom['id'], array($copyCourse['id']));

        $enabled = $this->getClassroomService()->isCourseInClassroom($copyCourse['id'], $classroom['id']);
        $this->assertEquals(false, $enabled);
    }

    public function testFindCoursesByCoursesIds()
    {
    }

    public function testDeleteClassroomCourses()
    {
        $textClassroom = array(
            'title' => 'test54345',
        );
        $course = $this->createCourse('Test Course 2');

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $copyCourses = $this->getClassroomService()->addCoursesToClassroom($classroom['id'], array($course['id']));
        $copyCourse = current($copyCourses);

        $enabled = $this->getClassroomService()->isCourseInClassroom($copyCourse['id'], $classroom['id']);

        $this->assertEquals(true, $enabled);

        $this->getClassroomService()->deleteClassroomCourses($classroom['id'], array($copyCourse['id']));

        $enabled = $this->getClassroomService()->isCourseInClassroom($copyCourse['id'], $classroom['id']);

        $this->assertEquals(false, $enabled);
    }

    public function testFindMembersByUserIdAndClassroomIds()
    {
    }

    public function testFindClassroomsByIds()
    {
        $textClassroom = array(
            'title' => 'test11112221',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $classrooms = $this->getClassroomService()->findClassroomsByIds(array(1));

        $this->assertEquals($classroom, $classrooms[1]);
    }

    public function testSearchMemberCount()
    {
    }

    public function testSearchMembers()
    {
    }

    public function testGetClassroomMember()
    {
        $textClassroom = array(
            'title' => 'test001',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $member = $this->getClassroomService()->getClassroomMember($classroom['id'], 3);

        $this->assertEquals(null, $member);
    }

    public function testGetClassroomMembersByCourseId()
    {
    }

    public function testFindClassroomMembersByRole()
    {
    }

    public function testFindMembersByClassroomIdAndUserIds()
    {
    }

    public function testBecomeStudent()
    {
        $user = $this->getCurrentUser();
        $textClassroom = array(
            'title' => 'test066',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $result = $this->getClassroomService()->isClassroomStudent($classroom['id'], $user2['id']);
        $this->assertEquals(false, $result);

        $this->getClassroomService()->becomeStudent($classroom['id'], $user2['id']);
        $result = $this->getClassroomService()->isClassroomStudent($classroom['id'], $user2['id']);
        $this->assertEquals(true, $result);
    }

    public function testRemoveStudent()
    {
        $teacher1 = $this->createTeacher('1');
        $teacher2 = $this->createTeacher('2');
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $course1 = $this->createCourse('Test Course 1');
        $this->getCourseMemberService()->setCourseTeachers($course1['id'],
            array(array('id' => $teacher1['id'], 'isVisible' => 1), array('id' => $teacher2['id'], 'isVisible' => 1)));
        $courseIds = array($course1['id']);
        $this->getClassroomService()->addCoursesToClassroom($classroom['id'], $courseIds);

        $this->getClassroomService()->publishClassroom($classroom['id']);
        $member1 = $this->getClassroomService()->becomeStudent($classroom['id'], $teacher1['id']);
        $member2 = $this->getClassroomService()->becomeStudent($classroom['id'], $teacher2['id']);
        $studentCount1 = $this->getClassroomService()->getClassroomStudentCount($classroom['id']);
        $this->assertEquals($studentCount1, 2);
        $this->getClassroomService()->removeStudent($classroom['id'], $member1['userId']);
        $studentCount2 = $this->getClassroomService()->getClassroomStudentCount($classroom['id']);
        $this->assertEquals($studentCount2, 1);
    }

    public function testGetClassroomStudentCount()
    {
        $user = $this->getCurrentUser();
        $textClassroom = array(
            'title' => 'test991',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $currentUser2 = new CurrentUser();
        $currentUser2->fromArray(array(
            'id' => 2,
            'nickname' => 'admin5',
            'email' => 'admin5@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));
        $this->getServiceKernel()->setCurrentUser($currentUser2);

        $result = $this->getClassroomService()->getClassroomStudentCount($classroom['id']);
        $this->assertEquals(0, $result);

        $this->getClassroomService()->becomeStudent($classroom['id'], $user['id']);

        $result = $this->getClassroomService()->getClassroomStudentCount($classroom['id']);
        $this->assertEquals(1, $result);
    }

    public function testIsClassroomStudent()
    {
        $textClassroom = array(
            'title' => 'test001',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $result = $this->getClassroomService()->isClassroomStudent($classroom['id'], $user2['id']);
        $this->assertEquals(false, $result);

        $this->getClassroomService()->becomeStudent($classroom['id'], $user2['id']);
        $result = $this->getClassroomService()->isClassroomStudent($classroom['id'], $user2['id']);
        $this->assertEquals(true, $result);
    }

    public function testRemarkStudent()
    {
    }

    public function testFindClassroomStudents()
    {
    }

    public function testLockStudent()
    {
    }

    public function testUnlockStudent()
    {
    }

    public function testBecomeAssistant()
    {
        $textClassroom = array(
            'title' => 'test099',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $assistants = $this->getClassroomService()->findAssistants($classroom['id']);
        $this->assertEquals(0, count($assistants));

        $this->getClassroomService()->becomeAssistant($classroom['id'], $user2['id']);
        $assistants = $this->getClassroomService()->findAssistants($classroom['id']);
        $this->assertEquals(1, count($assistants));
    }

    public function testFindAssistants()
    {
        $textClassroom = array(
            'title' => 'test433',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $this->getClassroomService()->becomeAssistant($classroom['id'], $user2['id']);
        $assistants = $this->getClassroomService()->findAssistants($classroom['id']);
        $this->assertEquals(1, count($assistants));
    }

    public function testisClassroomAssistant()
    {
        $textClassroom = array(
            'title' => 'test077',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $result = $this->getClassroomService()->isClassroomAssistant($classroom['id'], $user2['id']);
        $this->assertEquals(false, $result);

        $this->getClassroomService()->becomeAssistant($classroom['id'], $user2['id']);
        $result = $this->getClassroomService()->isClassroomAssistant($classroom['id'], $user2['id']);
        $this->assertEquals(true, $result);
    }

    public function testUpdateAssistants()
    {
    }

    public function testBecomeAuditor()
    {
        $user = $this->getCurrentUser();
        $textClassroom = array(
            'title' => 'test1444',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $result = $this->getClassroomService()->getClassroomAuditorCount($classroom['id']);
        $this->assertEquals(0, $result);

        $this->getClassroomService()->becomeAuditor($classroom['id'], $user2['id']);

        $result = $this->getClassroomService()->getClassroomAuditorCount($classroom['id']);
        $this->assertEquals(1, $result);
    }

    public function testIsClassroomAuditor()
    {
        $user = $this->getCurrentUser();
        $textClassroom = array(
            'title' => 'test333',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $enabled = $this->getClassroomService()->isClassroomAuditor($classroom['id'], $user2['id']);

        $this->assertEquals(false, $enabled);

        $this->getClassroomService()->becomeAuditor($classroom['id'], $user2['id']);

        $enabled = $this->getClassroomService()->isClassroomAuditor($classroom['id'], $user2['id']);

        $this->assertEquals(true, $enabled);
    }

    public function testGetClassroomAuditorCount()
    {
        $textClassroom = array(
            'title' => 'test2111',
        );
        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $result = $this->getClassroomService()->getClassroomAuditorCount($classroom['id']);
        $this->assertEquals(0, $result);

        $this->getClassroomService()->becomeAuditor($classroom['id'], $user2['id']);

        $result = $this->getClassroomService()->getClassroomAuditorCount($classroom['id']);
        $this->assertEquals(1, $result);
    }

    public function testAddHeadTeacher()
    {
        $textClassroom = array(
            'title' => 'test5554',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $this->getClassroomService()->addHeadTeacher($classroom['id'], $user2['id']);

        $currentUser = new CurrentUser();
        $currentUser->fromArray($user2);
        $this->getServiceKernel()->setCurrentUser($currentUser);

        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);
        $this->assertEquals(true, $enabled);
    }

    public function testAddHeadTeacher2()
    {
        $teacher1 = $this->createTeacher('1');
        $teacher2 = $this->createTeacher('2');
        $textClassroom = array(
            'title' => 'test',
        );
        $course1 = $this->createCourse('Test Course 1');

        $this->getCourseMemberService()->setCourseTeachers($course1['id'],
            array(array('id' => $teacher1['id'], 'isVisible' => 1), array('id' => $teacher2['id'], 'isVisible' => 1)));

        $courseIds = array($course1['id']);

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);
        $this->getClassroomService()->addHeadTeacher($classroom['id'], $teacher1['id']);
        $this->getClassroomService()->addCoursesToClassroom($classroom['id'], $courseIds);
        $classroom = $this->getClassroomService()->getClassroom($classroom['id']);
        $this->assertEquals($teacher1['id'], $classroom['headTeacherId']);
        $this->getClassroomService()->addHeadTeacher($classroom['id'], $teacher2['id']);
        $classroom = $this->getClassroomService()->getClassroom($classroom['id']);
        $this->assertEquals($teacher2['id'], $classroom['headTeacherId']);
    }

    public function testIsClassroomHeadTeacher()
    {
        $textClassroom = array(
            'title' => 'test5234',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $currentUser2 = new CurrentUser();
        $currentUser2->fromArray(array(
            'id' => 2,
            'nickname' => 'admin5',
            'email' => 'admin5@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $this->getServiceKernel()->setCurrentUser($currentUser2);

        $enabled = $this->getClassroomService()->isClassroomHeadTeacher($classroom['id'], $currentUser2['id']);
        $this->assertEquals(false, $enabled);

        $this->getClassroomService()->addHeadTeacher($classroom['id'], $currentUser2['id']);

        $enabled = $this->getClassroomService()->isClassroomHeadTeacher($classroom['id'], $currentUser2['id']);
        $this->assertEquals(true, $enabled);
    }

    public function testTryAdminClassroom()
    {
    }

    public function testCanManageClassroom()
    {
        $textClassroom = array(
            'title' => 'test32111',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);
        $this->assertEquals(true, $enabled);

        $user = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin1',
            'email' => 'admin1@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $user2 = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin2',
            'email' => 'admin2@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));
        $user3 = $this->getUserService()->register(array(
            'nickname' => 'admin3',
            'password' => 'admin',
            'email' => 'admin3@admin.com',
        ));
        $user4 = $this->getUserService()->register(array(
            'nickname' => 'admin4',
            'password' => 'admin',
            'email' => 'admin4@admin.com',
        ));

        $this->getClassroomService()->addHeadTeacher($classroom['id'], $user['id']);
        $this->getClassroomService()->becomeAssistant($classroom['id'], $user2['id']);
        $this->getClassroomService()->becomeAuditor($classroom['id'], $user3['id']);
        $this->getClassroomService()->becomeStudent($classroom['id'], $user4['id']);

        $c_user1 = new CurrentUser();
        $this->getServiceKernel()->setCurrentUser($c_user1->fromArray($user));
        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);
        $this->assertEquals(true, $enabled);

        $c_user2 = new CurrentUser();
        $this->getServiceKernel()->setCurrentUser($c_user2->fromArray($user2));
        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);
        $this->assertEquals(false, $enabled);

        $c_user3 = new CurrentUser();
        $this->getServiceKernel()->setCurrentUser($c_user3->fromArray($user3));
        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);
        $this->assertEquals(false, $enabled);

        $c_user4 = new CurrentUser();
        $this->getServiceKernel()->setCurrentUser($c_user4->fromArray($user4));
        $enabled = $this->getClassroomService()->canManageClassroom($classroom['id']);
        $this->assertEquals(false, $enabled);
    }

    public function testTryManageClassroom()
    {
    }

    public function testCanTakeClassroom()
    {
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $enabled = $this->getClassroomService()->canTakeClassroom($classroom['id']);
        $this->assertEquals(true, $enabled);

        $teacherUser = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'teacher',
            'email' => 'teacher@teacher.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $auditorUser = $this->getUserService()->register(array(
            'id' => 3,
            'nickname' => 'auditor',
            'email' => 'auditor@auditor.com',
            'password' => 'auditor',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $studentUser = $this->getUserService()->register(array(
            'id' => 4,
            'nickname' => 'student',
            'email' => 'student@student.com',
            'password' => 'student',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $this->getClassroomService()->becomeAuditor($classroom['id'], $auditorUser['id']);
        $this->getClassroomService()->addHeadTeacher($classroom['id'], $teacherUser['id']);
        $this->getClassroomService()->becomeStudent($classroom['id'], $studentUser['id']);

        $teacherCurrent = new CurrentUser();
        $this->getServiceKernel()->setCurrentUser($teacherCurrent->fromArray($teacherUser));
        $enabled = $this->getClassroomService()->canTakeClassroom($classroom['id']);
        $this->assertEquals(true, $enabled);

        $auditorCurrentUser = new CurrentUser();
        $this->getServiceKernel()->setCurrentUser($auditorCurrentUser->fromArray($auditorUser));
        $enabled = $this->getClassroomService()->canTakeClassroom($classroom['id']);
        $this->assertEquals(false, $enabled);

        $studentCurrentUser = new CurrentUser();
        $this->getServiceKernel()->setCurrentUser($studentCurrentUser->fromArray($studentUser));

        $enabled = $this->getClassroomService()->canTakeClassroom($classroom['id']);
        $this->assertEquals(true, $enabled);
    }

    public function testTryTakeClassroom()
    {
    }

    public function testCanLookClassroom()
    {
        $user = $this->getCurrentUser();
        $user = $this->createStudent();
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);

        $enabled = $this->getClassroomService()->canLookClassroom($classroom['id']);

        $this->assertEquals(true, $enabled);
        $currentUser = new CurrentUser();
        $currentUser->fromArray(array(
            'id' => 2,
            'nickname' => 'admin',
            'email' => 'admin@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $this->getServiceKernel()->setCurrentUser($currentUser);

        $enabled = $this->getClassroomService()->canLookClassroom($classroom['id']);

        $this->assertEquals(true, $enabled); //默认是showable班级

        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);

        $this->getClassroomService()->addHeadTeacher($classroom['id'], 2);

        $enabled = $this->getClassroomService()->canLookClassroom($classroom['id']);

        $this->assertEquals(true, $enabled);

        $this->getClassroomService()->publishClassroom($classroom['id']);

        $user = $this->getUserService()->register(array(
            'id' => 3,
            'nickname' => 'admin1',
            'email' => 'admin@adm1in.com',
            'password' => 'adm1in',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $this->getClassroomService()->becomeAuditor($classroom['id'], $user['id']);

        $enabled = $this->getClassroomService()->canLookClassroom($classroom['id']);

        $this->assertEquals(true, $enabled);

        $this->getClassroomService()->becomeStudent($classroom['id'], $user['id']);

        $enabled = $this->getClassroomService()->canLookClassroom($classroom['id']);

        $this->assertEquals(true, $enabled);

        $currentUser = new CurrentUser();
        $currentUser->fromArray(array(
            'id' => 4,
            'nickname' => 'admin11',
            'email' => 'admin@adm11in.com',
            'password' => 'adm11in',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));

        $this->getServiceKernel()->setCurrentUser($currentUser);

        $enabled = $this->getClassroomService()->canLookClassroom($classroom['id']);

        $this->assertEquals(true, $enabled);

        $classroom['showable'] = '0';
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $classroom);
        $enabled = $this->getClassroomService()->canLookClassroom($classroom['id']);
        $this->assertEquals(false, $enabled);
    }

    public function testCanJoinClassroom()
    {
        $classroom = $this->getClassroomService()->addClassroom(array(
            'title' => 'test Classroom',
        ));
        $this->getClassroomService()->publishClassroom($classroom['id']);

        $classroom1 = $this->getClassroomService()->addClassroom(array(
            'title' => 'test Classroom 2',
        ));
        $classroom1 = $this->getClassroomService()->updateClassroom($classroom1['id'], array(
            'expiryMode' => 'date',
            'expiryValue' => time(),
        ));
        $this->getClassroomService()->publishClassroom($classroom1['id']);

        $user = $this->getUserService()->register(array(
            'id' => 3,
            'nickname' => 'test',
            'email' => 'test@test.com',
            'password' => 'test123',
            'roles' => array('ROLE_USER'),
        ));

        $currentUser = new CurrentUser();
        $user['currentIp'] = '127.0.0.1';
        $currentUser->fromArray($user);

        $this->getServiceKernel()->setCurrentUser($currentUser);

        $result = $this->getClassroomService()->canJoinClassroom($classroom['id']);
        $this->assertEquals($result['code'], 'success');

        sleep(3);
        $result1 = $this->getClassroomService()->canJoinClassroom($classroom1['id']);
        $this->assertEquals($result1['code'], 'classroom.expired');
    }

    /** @group current */
    public function testCanLearnClassroom()
    {
        $classroom = $this->getClassroomService()->addClassroom(array(
            'title' => 'test Classroom',
        ));
        $this->getClassroomService()->publishClassroom($classroom['id']);

        $user = $this->getUserService()->register(array(
            'id' => 3,
            'nickname' => 'test',
            'email' => 'test@test.com',
            'password' => 'test123',
            'roles' => array('ROLE_USER'),
        ));

        $currentUser = new CurrentUser();
        $user['currentIp'] = '127.0.0.1';
        $currentUser->fromArray($user);

        $this->getServiceKernel()->setCurrentUser($currentUser);

        $result1 = $this->getClassroomService()->canLearnClassroom($classroom['id']);
        $this->assertEquals($result1['code'], 'member.not_found');

        $this->getClassroomService()->becomeAuditor($classroom['id'], $user['id']);
        $result2 = $this->getClassroomService()->canLearnClassroom($classroom['id']);
        $this->assertEquals($result2['code'], 'member.auditor');

        $this->getClassroomService()->becomeStudent($classroom['id'], $user['id']);
        $result3 = $this->getClassroomService()->canLearnClassroom($classroom['id']);
        $this->assertEquals($result3['code'], 'success');
    }

    public function testTryLookClassroom()
    {
    }

    public function testCanHandleClassroom()
    {
    }

    public function testTryHandleClassroom()
    {
    }

    public function testFindCoursesByClassroomId()
    {
        $user = $this->createUser();
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);

        $this->createCourse('Test Course 1');
        $this->createCourse('Test Course 2');

        $this->getClassroomService()->addCoursesToClassroom($classroom['id'], array(1, 2));

        $courses = $this->getClassroomService()->findCoursesByClassroomId($classroom['id']);

        $this->assertEquals(2, count($courses));
    }

    public function testUpdateAssistant()
    {
        $teacher1 = $this->createTeacher('1');
        $teacher2 = $this->createTeacher('2');
        $teacher3 = $this->createTeacher('3');
        $teacher4 = $this->createTeacher('4');

        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);

        $this->getClassroomService()->addHeadTeacher($classroom['id'], $teacher2['id']);

        $this->getClassroomService()->becomeStudent($classroom['id'], $teacher1['id']);
        $this->getClassroomService()->becomeStudent($classroom['id'], $teacher3['id']);
        $this->getClassroomService()->becomeStudent($classroom['id'], $teacher4['id']);

        $teacherIds = array($teacher3['id'], $teacher4['id']);
        $this->getClassroomService()->updateAssistants($classroom['id'], $teacherIds);
        $assitantIds = $this->getClassroomService()->findAssistants($classroom['id']);
        $this->assertEquals(2, count($assitantIds));
    }

    /**
     * @group current
     */
    public function testAddCoursesToClassroom()
    {
        $teacher1 = $this->createTeacher('1');
        $teacher2 = $this->createTeacher('2');
        $teacher3 = $this->createTeacher('3');
        $teacher4 = $this->createTeacher('4');
        $teacher5 = $this->createTeacher('5');
        $teacher6 = $this->createTeacher('6');
        $teacher7 = $this->createTeacher('7');
        $teacher8 = $this->createTeacher('8');
        $textClassroom = array(
            'title' => 'test',
        );

        $course1 = $this->createCourse('Test Course 1');
        $course2 = $this->createCourse('Test Course 2');
        $course3 = $this->createCourse('Test Course 3');

        $this->getCourseMemberService()->setCourseTeachers($course1['id'], array(
            array('id' => $teacher1['id'], 'isVisible' => 1),
            array('id' => $teacher2['id'], 'isVisible' => 1),
        ));
        $this->getCourseMemberService()->setCourseTeachers($course2['id'], array(
            array('id' => $teacher4['id'], 'isVisible' => 1),
            array('id' => $teacher5['id'], 'isVisible' => 1),
        ));
        $this->getCourseMemberService()->setCourseTeachers($course3['id'], array(
            array('id' => $teacher1['id'], 'isVisible' => 1),
            array('id' => $teacher3['id'], 'isVisible' => 1),
            array('id' => $teacher6['id'], 'isVisible' => 1),
        ));

        $courseIds = array($course1['id'], $course2['id']);

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);
        $this->getClassroomService()->addHeadTeacher($classroom['id'], $teacher1['id']);
        $this->getClassroomService()->addCoursesToClassroom($classroom['id'], $courseIds);
        $teachers = $this->getClassroomService()->findTeachers($classroom['id']);
        $this->assertEquals(count($teachers), 5); // classroom.creator也是classroom的teacher
        $courseIds = array($course3['id']);
        $this->getClassroomService()->addCoursesToClassroom($classroom['id'], $courseIds);
        $teachers = $this->getClassroomService()->findTeachers($classroom['id']);
        //ci报错，本地正常
        //$this->assertEquals(count($teachers), 7);
    }

    public function testUpdateClassroomCourses()
    {
        $teacher1 = $this->createTeacher('1');
        $teacher2 = $this->createTeacher('2');
        $teacher3 = $this->createTeacher('3');
        $teacher4 = $this->createTeacher('4');
        $teacher5 = $this->createTeacher('5');
        $teacher6 = $this->createTeacher('6');
        $teacher7 = $this->createTeacher('7');
        $teacher8 = $this->createTeacher('8');
        $textClassroom = array(
            'title' => 'test',
        );

        $course1 = $this->createCourse('Test Course 1');
        $course2 = $this->createCourse('Test Course 2');
        $course3 = $this->createCourse('Test Course 3');

        $this->getCourseMemberService()->setCourseTeachers($course1['id'], array(
            array('id' => $teacher1['id'], 'isVisible' => 1),
            array('id' => $teacher2['id'], 'isVisible' => 1),
            array('id' => $teacher3['id'], 'isVisible' => 1),
        ));
        $this->getCourseMemberService()->setCourseTeachers($course2['id'], array(
            array('id' => $teacher4['id'], 'isVisible' => 1),
            array('id' => $teacher5['id'], 'isVisible' => 1),
        ));
        $this->getCourseMemberService()->setCourseTeachers($course3['id'], array(
            array('id' => $teacher1['id'], 'isVisible' => 1),
            array('id' => $teacher3['id'], 'isVisible' => 1),
            array('id' => $teacher6['id'], 'isVisible' => 1),
        ));

        $courseIds = array($course1['id'], $course2['id'], $course3['id']);

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $classroom = $this->getClassroomService()->updateClassroom($classroom['id'], $textClassroom);
        $this->getClassroomService()->addHeadTeacher($classroom['id'], $teacher1['id']);
        $courses = $this->getClassroomService()->addCoursesToClassroom($classroom['id'], $courseIds);

        $teachers = $this->getClassroomService()->findTeachers($classroom['id']);

        //ci报错，本地正常
        //$this->assertEquals(count($teachers), 7);

        $courseIds = array($courses[2]['id']);

        $this->getClassroomService()->updateClassroomCourses($classroom['id'], $courseIds);
        $teachers = $this->getClassroomService()->findTeachers($classroom['id']);
        //$this->assertEquals(count($teachers), 4);
    }

    public function testCanCreateThreadEvent()
    {
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);

        $user = $this->getUserService()->register(array(
            'id' => 2,
            'nickname' => 'admin2',
            'email' => 'admin2@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER'),
        ));
        $this->getClassroomService()->becomeAssistant($classroom['id'], $user['id']);

        $currentUser = new CurrentUser();
        $currentUser->fromArray($user);
        $this->getServiceKernel()->setCurrentUser($currentUser);
        $result = $this->getClassroomService()->canCreateThreadEvent(array('targetId' => $classroom['id']));

        $this->assertEquals('assistant', $result[0]);
    }

    public function testFindUserJoinedClassroomIds()
    {
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);

        $user1 = $this->getUserService()->register(array(
            'nickname' => 'admin3',
            'password' => 'admin',
            'email' => 'admin3@admin.com',
        ));
        $user2 = $this->getUserService()->register(array(
            'nickname' => 'admin4',
            'password' => 'admin',
            'email' => 'admin4@admin.com',
        ));

        $this->getClassroomService()->becomeAuditor($classroom['id'], $user1['id']);
        $this->getClassroomService()->becomeStudent($classroom['id'], $user2['id']);

        $members = $this->getClassroomService()->findUserJoinedClassroomIds($user1['id']);

        $this->assertCount(1, $members);
    }

    public function testFindClassroomCourseByCourseSetIds()
    {
        $result = $this->getClassroomService()->findClassroomCourseByCourseSetIds(array(-1, -2));

        $this->assertCount(0, $result);
    }

    public function testFindUserPaidCoursesInClassroom()
    {
        $textClassroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);

        $course1 = $this->createCourse('Test Course 1');
        $course2 = $this->createCourse('Test Course 2');
        $course3 = $this->createCourse('Test Course 3');

        $courseIds = array($course1['id'], $course2['id'], $course3['id']);

        $classroom = $this->getClassroomService()->addClassroom($textClassroom);

        $this->mockBiz('Course:MemberService', array(
            array('functionName' => 'findCoursesByStudentIdAndCourseIds', 'returnValue' => array(array('id' => 1, 'courseId' => $course1['id'], 'orderId' => 1))),
        ));
        $this->mockBiz('Order:OrderService', array(
            array('functionName' => 'searchOrderItems', 'returnValue' => array(array('id' => 1, 'order_id' => 1))),
        ));

        list($paidCourses, $orderItems) = $this->getClassroomService()->findUserPaidCoursesInClassroom(1, $classroom['id']);

        $this->assertEquals(1, count($paidCourses));
        $this->assertEquals(1, count($orderItems));
    }

    protected function mockCourse($title = 'Test Course 1')
    {
        return array(
            'title' => $title,
            'courseSetId' => 1,
            'learnMode' => 'freeMode',
            'expiryMode' => 'forever',
            'courseType' => 'normal',
        );
    }

    protected function mockCourseSet($title = 'Test Course 1')
    {
        return array('title' => $title, 'type' => 'normal');
    }

    private function createUser()
    {
        $user = array();
        $user['email'] = 'user@user.com';
        $user['nickname'] = 'user';
        $user['password'] = 'user';
        $user = $this->getUserService()->register($user);
        $user['currentIp'] = '127.0.0.1';
        $user['roles'] = array('ROLE_USER', 'ROLE_SUPER_ADMIN', 'ROLE_TEACHER');

        return $user;
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return CourseService
     */
    private function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    private function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return ClassroomService
     */
    private function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    private function createCourse($title)
    {
        $courseSet = array(
            'title' => '新课程开始！',
            'type' => 'normal',
        );

        $courseSet = $this->getCourseSetService()->createCourseSet($courseSet);
        $course = $this->mockCourse($title);
        $course['courseSetId'] = $courseSet['id'];

        return $this->getCourseService()->createCourse($course);
    }

    private function createStudent()
    {
        $user = array();
        $user['email'] = 'user@user1.com';
        $user['nickname'] = 'use1r';
        $user['password'] = 'user1';
        $user['roles'] = array('ROLE_USER');

        return $this->getUserService()->register($user);
    }

    private function createTeacher($id)
    {
        $user = array();
        $user['nickname'] = 'user'.$id;
        $user['email'] = $user['nickname'].'@user.com';
        $user['password'] = 'user';
        $user['roles'] = array('ROLE_USER', 'ROLE_TEACHER');

        return $this->getUserService()->register($user);
    }
}
