<?php

namespace Tests\Unit\Classroom;

use Biz\BaseTestCase;
use Biz\Classroom\Service\ClassroomReviewService;

class ClassroomReviewServiceTest extends BaseTestCase
{
    public function testGetReview()
    {
        $user = $this->getCurrentUser();
        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields = array(
            'title' => 'test',
            'content' => 'test_content',
            'userId' => $user['id'],
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $review = $this->getClassRoomReviewService()->saveReview($fields);
        $result = $this->getClassRoomReviewService()->getReview($classroom['id']);
        $this->assertEquals('test', $result['title']);
    }

    public function testSearchReviews()
    {
        $user = $this->getCurrentUser();
        $user1 = $this->createStudentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user1['id'],
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);

        $fields2 = array(
            'title' => 'test2',
            'content' => 'test_content',
            'userId' => $user['id'],
            'classroomId' => $classroom['id'],
            'rating' => 2,
        );
        $this->getClassRoomReviewService()->saveReview($fields2);

        $results = $this->getClassRoomReviewService()->searchReviews(array('classroomId' => $classroom['id']), array('rating' => 'DESC'), 0, 5);
        $this->assertCount(2, $results);
    }

    public function testSearchReviewCount()
    {
        $user = $this->getCurrentUser();
        $user1 = $this->createStudentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user1['id'],
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);

        $fields2 = array(
            'title' => 'test2',
            'content' => 'test_content',
            'userId' => $user['id'],
            'classroomId' => $classroom['id'],
            'rating' => 2,
        );
        $this->getClassRoomReviewService()->saveReview($fields2);

        $results = $this->getClassRoomReviewService()->searchReviewCount(array('classroomId' => $classroom['id']));
        $this->assertEquals(2, $results);
    }

    public function testGetUserClassroomReviewWithExistId()
    {
        $user = $this->getCurrentUser();
        $user1 = $this->createStudentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user1['id'],
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);

        $fields2 = array(
            'title' => 'test2',
            'content' => 'test_content',
            'userId' => $user['id'],
            'classroomId' => $classroom['id'],
            'rating' => 2,
        );
        $this->getClassRoomReviewService()->saveReview($fields2);

        $result = $this->getClassRoomReviewService()->getUserClassroomReview($user['id'], $classroom['id']);
        $this->assertEquals('test2', $result['title']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testGetUserClassroomReviewWithNotExistId()
    {
        $user = $this->getCurrentUser();
        $user1 = $this->createStudentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user1['id'],
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);

        $fields2 = array(
            'title' => 'test2',
            'content' => 'test_content',
            'userId' => $user['id'],
            'classroomId' => $classroom['id'],
            'rating' => 2,
        );
        $this->getClassRoomReviewService()->saveReview($fields2);

        $result = $this->getClassRoomReviewService()->getUserClassroomReview($user['id'], $classroom['id'] + 1);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testSaveReviewWithoutUserId()
    {
        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);

        $fields2 = array(
            'title' => 'test2',
            'content' => 'test_content',
            'classroomId' => $classroom['id'],
            'rating' => 2,
        );
        $this->getClassRoomReviewService()->saveReview($fields2);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\AccessDeniedException
     */
    public function testSaveReviewWithNotExistClassroom()
    {
        $user1 = $this->createStudentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user1['id'],
            'classroomId' => $classroom['id'] + 100,
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testSaveReviewWithNotExistUser()
    {
        $user = $this->getCurrentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user['id'] + 10,
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);
    }

    public function testSaveReviewWithExistReview()
    {
        $user = $this->getCurrentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user['id'],
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $this->getClassRoomReviewService()->saveReview($fields1);
        $fields1['title'] = 'test2';
        $result = $this->getClassRoomReviewService()->saveReview($fields1);

        $this->assertEquals('test2', $result['title']);
    }

    public function testDeleteReviewWithExistReview()
    {
        $user = $this->getCurrentUser();

        $classroom = array(
            'title' => 'test',
        );
        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $fields1 = array(
            'title' => 'test1',
            'content' => 'test_content',
            'userId' => $user['id'],
            'classroomId' => $classroom['id'],
            'rating' => 1,
        );
        $review = $this->getClassRoomReviewService()->saveReview($fields1);
        $this->getClassRoomReviewService()->deleteReview($review['id']);
        $review = $this->getClassRoomReviewService()->getReview($review['id']);
        $this->assertNull($review);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testDeleteReviewWithNotExistReview()
    {
        $classroom = array(
            'title' => 'test',
        );

        $classroom = $this->getClassroomService()->addClassroom($classroom);
        $this->getClassroomService()->publishClassroom($classroom['id']);
        $this->getClassRoomReviewService()->deleteReview(100);
    }

    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return ClassroomReviewService
     */
    protected function getClassRoomReviewService()
    {
        return $this->createService('Classroom:ClassroomReviewService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    private function createStudentUser()
    {
        $user = array();
        $user['email'] = 'userStudent@userStudent.com';
        $user['nickname'] = 'userStudent';
        $user['password'] = 'userStudent';
        $user = $this->getUserService()->register($user);
        $user['currentIp'] = '127.0.0.1';
        $user['roles'] = array('ROLE_USER');

        return $user;
    }

    private function createTeacherUser()
    {
        $user = array();
        $user['email'] = 'teacherUser@user.com';
        $user['nickname'] = 'teacherUser';
        $user['password'] = 'teacherUser';
        $user = $this->getUserService()->register($user);
        $user['currentIp'] = '127.0.0.1';
        $user['roles'] = array('ROLE_USER', 'ROLE_TEACHER');

        return $user;
    }

    private function createNormalUser()
    {
        $user = array();
        $user['email'] = 'normal@user.com';
        $user['nickname'] = 'normal';
        $user['password'] = 'user';
        $user = $this->getUserService()->register($user);
        $user['currentIp'] = '127.0.0.1';
        $user['roles'] = array('ROLE_USER');

        return $user;
    }
}
