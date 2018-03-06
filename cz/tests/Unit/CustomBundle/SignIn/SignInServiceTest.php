<?php

namespace Tests\Unit\CustomBundle\SignIn;

use Biz\BaseTestCase;

class SignInServiceTest extends BaseTestCase
{
    public function testCreateSignIn()
    {
        $sign = $this->mockSignIn();

        $affected = $this->getSignInService()->createSignIn($sign);

        $this->assertEquals($affected['lessonId'], $sign['lessonId']);
        $this->assertEquals($affected['verifyCode'], $sign['verifyCode']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateSignInLackRequiredFields()
    {
        $sign = array(
            'lessonId' => 1,
            'verifyCode' => '982712',
        );

        $affected = $this->getSignInService()->createSignIn($sign);
    }

    public function testEndSignIn()
    {
        $sign = $this->mockSignIn();
        $course = $this->mockCourse();
        $courseLesson = $this->mockCourseLesson();

        $course = $this->getCourseService()->createCourse($course);
        $lesson = $this->getLessonTaskService()->createCourseLesson($courseLesson);
        $affected = $this->getSignInService()->createSignIn($sign);

        $updated = $this->getSignInService()->endSignIn($affected['id']);

        $this->assertEquals($updated['status'], 'end');
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testEndSignInWhileNotExist()
    {
        $sign = $this->mockSignIn();

        $affected = $this->getSignInService()->createSignIn($sign);

        $updated = $this->getSignInService()->endSignIn($affected['id'] + 1);
    }

    public function testCancelSignIn()
    {
        $sign = $this->mockSignIn();

        $affected = $this->getSignInService()->createSignIn($sign);

        $this->getSignInService()->cancelSignIn($affected['id']);
        $sign = $this->getSignInService()->getSignIn($affected['id']);

        $this->assertNull($sign);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testCancelSignInWhileNotExist()
    {
        $sign = $this->mockSignIn();

        $affected = $this->getSignInService()->createSignIn($sign);

        $this->getSignInService()->cancelSignIn($affected['id'] + 1);
    }

    public function testGetSignIn()
    {
        $sign = $this->mockSignIn();

        $affected = $this->getSignInService()->createSignIn($sign);

        $sign = $this->getSignInService()->getSignIn($affected['id']);

        $this->assertEquals($affected, $sign);
    }

    public function testGetSignInByLessonIdAndTime()
    {
        $sign = $this->mockSignIn();

        $affected = $this->getSignInService()->createSignIn($sign);

        $sign = $this->getSignInService()->getSignInByLessonIdAndTime($sign['lessonId'], $sign['time']);

        $this->assertEquals($affected, $sign);
    }

    public function testCreateSignInMember()
    {
        $member = $this->mockSignMember();

        $affected = $this->getSignInService()->createSignInMember($member);

        $this->assertEquals($member['lessonId'], $affected['lessonId']);
        $this->assertEquals($member['signinId'], $affected['signinId']);
        $this->assertEquals($member['userId'], $affected['userId']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateSignInMemberWhileLackRequiredFields()
    {
        $member = array(
            'lessonId' => 1,
            'time' => 1,
            'signinId' => 1,
        );

        $affected = $this->getSignInService()->createSignInMember($member);
    }

    public function testAttendSignIn()
    {
        $member = $this->mockSignMember();

        $affected = $this->getSignInService()->createSignInMember($member);

        $updated = $this->getSignInService()->attendSignIn($affected['id']);
        $this->assertEquals($updated['status'], 'attend');
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testAttendSignInWhileNotExist()
    {
        $member = $this->mockSignMember();

        $affected = $this->getSignInService()->createSignInMember($member);

        $updated = $this->getSignInService()->attendSignIn($affected['id'] + 1);
    }

    public function testGetSignInMember()
    {
        $member = $this->mockSignMember();

        $affected = $this->getSignInService()->createSignInMember($member);
        $member = $this->getSignInService()->getSignInMember($affected['id']);
        $this->assertEquals($member, $affected);
    }

    public function testGetSignInMemberBySignInIdAndUserId()
    {
        $member = $this->mockSignMember();

        $affected = $this->getSignInService()->createSignInMember($member);
        $member = $this->getSignInService()->getSignInMemberBySignInIdAndUserId($affected['signinId'], $affected['userId']);

        $this->assertEquals($member, $affected);
    }

    // public function testStudentSignIn()
    // {
    //     $signIn = $this->mockSignIn();
    //     $signIn = $this->getSignInService()->createSignIn($signIn);

    //     $member = array(
    //         'lessonId' => $signIn['lessonId'],
    //         'courseId' => 1,
    //         'time' => 1,
    //         'signinId' => $signIn['id'],
    //         'userId' => 1,
    //     );
    //     $member = $this->getSignInService()->createSignInMember($member);

    //     $this->getSignInService()->studentSignIn(1, $signIn['id'], array('code' => '982712'));

    //     $member = $this->getSignInService()->getSignInMember($member['id']);

    //     $this->assertEquals($member['status'], 'attend');
    // }

    // /**
    //  * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
    //  */
    // public function testStudentSignInWhileNotExistSignIn()
    // {
    //     $signIn = $this->mockSignIn();
    //     $signIn = $this->getSignInService()->createSignIn($signIn);

    //     $member = array(
    //         'lessonId' => $signIn['lessonId'],
    //         'time' => 1,
    //         'signinId' => $signIn['id'],
    //         'courseId' => 1,
    //         'userId' => 1,
    //     );
    //     $member = $this->getSignInService()->createSignInMember($member);

    //     $this->getSignInService()->studentSignIn(1, $signIn['id'] + 1, array('code' => '982712'));
    // }

    // /**
    //  * @expectedException \Codeages\Biz\Framework\Service\Exception\AccessDeniedException
    //  */
    // public function testStudentSignInWhileWrongCode()
    // {
    //     $signIn = $this->mockSignIn();
    //     $signIn = $this->getSignInService()->createSignIn($signIn);

    //     $member = array(
    //         'lessonId' => $signIn['lessonId'],
    //         'courseId' => 1,
    //         'time' => 1,
    //         'signinId' => $signIn['id'],
    //         'userId' => 1,
    //     );
    //     $member = $this->getSignInService()->createSignInMember($member);

    //     $this->getSignInService()->studentSignIn(1, $signIn['id'], array('code' => '98272'));
    // }

    protected function mockSignIn()
    {
        return array(
            'lessonId' => 1,
            'courseId' => 1,
            'time' => 1,
            'verifyCode' => '982712',
        );
    }

    protected function mockSignMember()
    {
        return array(
            'lessonId' => 1,
            'courseId' => 1,
            'time' => 1,
            'signinId' => 1,
            'userId' => 1,
        );
    }
    protected function mockCourse()
    {
        return array(
            'title' => 1,
            'courseSetId' => 1,
            'expiryMode' => 'forever',
            'learnMode' => 'freeMode',
            'termCode' => 1,

        );
    }

    protected function mockCourseLesson()
    {
        return array(
            'courseId' => 1,
            'number' => 1,
            'seq' => 1,
            'title' => 'in',
            'status' => 'created',
        );
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getLessonTaskService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
