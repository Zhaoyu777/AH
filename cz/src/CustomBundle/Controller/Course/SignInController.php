<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class SignInController extends BaseController
{
    public function startAction(Request $request, $lessonId)
    {
        $time = $request->query->get('time');
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $signIns = $this->getSignInService()->findIngSignInByCourseId($lesson['courseId']);
        foreach ($signIns as $key => $signIn) {
            if (($signIn['createdTime'] + 45 * 60) < time() && ($signIn['status'] != 'end')) {
                $this->getSignInService()->endSignIn($signIn['id']);
                unset($signIns[$key]);
            }
        }

        if ($lesson['status'] == 'teached') {
            return $this->createJsonResponse(array('success' => 'error', 'message' => '课次已下课，不能再签到咯'));
        }

        if (!empty($signIns)) {
            return $this->createJsonResponse(array('success' => 'error', 'message' => '该班级已开始其他签到'));
        }
        $code = $this->generateSignCode(4);

        $signIn = array(
            'time' => $time,
            'verifyCode' => $code,
            'lessonId' => $lesson['id'],
            'courseId' => $lesson['courseId'],
        );
        $signIn = $this->getSignInService()->createSignIn($signIn);

        $count = $this->getSignInService()->countSignInMembers(array('signinId' => $signIn['id']));

        return $this->createJsonResponse(array(
            'code' => $code,
            'signIn' => $signIn,
            'count' => $count,
        ));
    }

    protected function generateSignCode($length, $string = '0123456789')
    {
        $code = '';
        $sLength = strlen($string);

        while (strlen($code) < $length) {
            $code .= $string[mt_rand(0, $sLength - 1)];
        }

        return $code;
    }

    public function cancelAction(Request $request)
    {
        $signInId = $request->request->get('signInId');
        $signIn = $this->getSignInService()->getSignIn($signInId);
        $this->getSignInService()->cancelSignIn($signInId);

        return $this->createJsonResponse($signIn);
    }

    public function endAction(Request $request)
    {
        $signInId = $request->request->get('signInId');

        $signIn = $this->getSignInService()->endSignIn($signInId);

        return $this->createJsonResponse($signIn);
    }

    public function resultAction(Request $request, $lessonId)
    {
        $time = $request->query->get('time');
        $signIn = $this->getSignInService()->getSignInByLessonIdAndTime($lessonId, $time);

        $members = $this->getSignInService()->findSignInMembersBySignInId($signIn['id']);

        $userIds = ArrayToolkit::column($members, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $members = ArrayToolkit::group($members, 'status');
        array_walk(
            $members,
            function (&$member) {
                $member = ArrayToolkit::index($member, 'userId');
            }
        );

        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        return $this->render('course-manage/custom-lesson/sign-in-result.html.twig', array(
            'members' => $members,
            'users' => $users,
            'signIn' => $signIn,
            'lessonId' => $lessonId,
            'lesson' => $lesson,
            'profiles' => $profiles,
        ));
    }

    public function attendMembersAction($signInId)
    {
        return $this->render(
            'course-manage/custom-lesson/sign-in-result-items/attend-students.html.twig',
            $this->getSignInResults($signInId, 'attend')
        );
    }

    public function absentMembersAction($signInId)
    {
        return $this->render(
            'course-manage/custom-lesson/sign-in-result-items/absent-students.html.twig',
            $this->getSignInResults($signInId, 'absent')
        );
    }

    private function getSignInResults($signInId, $status)
    {
        $members = $this->getSignInService()->findSignInMembersBySignInIdAndStatus($signInId, $status);

        $userIds = ArrayToolkit::column($members, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $members = ArrayToolkit::index($members, 'userId');

        return array(
            'members' => $members,
            'users' => $users,
            'profiles' => $profiles,
        );
    }

    public function setSignInStatusAction($signInMemberId, $status)
    {
        $currentUser = $this->getCurrentuser();
        $member = $this->getSignInService()->getSignInMember($signInMemberId);

        if ($member['status'] == $status) {
            return $this->createJsonResponse(array(
                'success' => 'error',
                'message' => '请勿重复设置',
            ));
        }

        $member = $this->getSignInService()->setSignInMemberStatus($signInMemberId, $status, $currentUser['id']);
        $user = $this->getUserService()->getUser($member['userId']);

        $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');

        $response = array(
            'id' => $member['id'],
            'nickname' => $user['number'],
            'truename' => $user['truename'],
            'userId' => $user['id'],
            'updatedTime' => empty($member['updatedTime']) ? $member['createdTime'] : $member['updatedTime'],
            'address' => empty($member['address']) ? '无签到位置信息。' : $member['address'],
            'avatar' => $this->getWebExtension()->getFilePath($avatar, 'avatar.png'),
        );

        $response['updatedTime'] = date('Y-m-d H:i:s', $response['updatedTime']);

        return $this->createJsonResponse($response);
    }

    public function setAbsentAction($signInMemberId)
    {
        $this->getSignInService()->absentSignIn($signInMemberId);

        return $this->createJsonResponse(true);
    }

    public function setAttendAction($signInMemberId)
    {
        $this->getSignInService()->attendSignIn($signInMemberId);

        return $this->createJsonResponse(true);
    }

    public function recordAction($courseId, $lessonId, $userId)
    {
        $records = $this->getSignInService()->findSignInMembersByLessonIdAndUserId($lessonId, $userId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        return $this->render('course-manage/custom-lesson/sign-in-record.html.twig', array(
            'records' => $records,
            'lesson' => $lesson,
            'courseId' => $courseId,
        ));
    }

    public function manageAction(Request $request, $courseId, $lessonId, $userId)
    {
        $signIn = $this->getSignInService()->getLastSignInByLessonId($lessonId);
        $count = 0;
        $attendCount = 0;
        $preview = $request->query->get('preview', false);
        if (!empty($signIn)) {
            $conditons = array(
                'signinId' => $signIn['id'],
            );
            $count = $this->getSignInService()->countSignInMembers($conditons);
            $conditons['status'] = 'attend';
            if ($signIn['status'] == 'end') {
                $attendCount = 0;
            } else {
                $attendCount = $this->getSignInService()->countSignInMembers($conditons);
            }
        }
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        return $this->render('course-manage/custom-lesson/sign-in-manage.html.twig', array(
            'signIn' => $signIn,
            'lesson' => $lesson,
            'attendCount' => $attendCount,
            'count' => $count,
            'preview' => $preview,
        ));
    }

    public function memberAddMatchAction(Request $request, $signInId)
    {
        $queryField = $request->query->get('q');
        if (empty($queryField)) {
            return $this->createJsonResponse(array());
        }

        $signIn = $this->getSignInService()->getSignIn($signInId);
        $teachers = $this->getCourseService()->findTeachersByCourseId($signIn['courseId']);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');

        $members = $this->getSignInService()->findSignInMembersBySignInId($signInId);
        $memberIds = ArrayToolkit::column($members, 'userId');

        $userIds = array_merge($teacherIds, $memberIds);
        $users = $this->getUserService()->searchAllUsers(
            array('queryField' => $queryField, 'excludeIds' => $userIds),
            array('createdTime' => 'DESC'),
            0,
            100
        );
        $userIds = ArrayToolkit::column($users, 'id');
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $members = array();

        foreach ($users as $user) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $members[] = array(
                'id' => $user['id'],
                'truename' => $user['truename'],
                'nickname' => $user['number'],
                'role' => in_array('ROLE_TEACHER', $user['roles']) ? '教师' : '学员',
                'avatar' => $this->getWebExtension()->getFilePath($avatar, 'avatar.png'),
                'isVisible' => 1,
            );
        }

        return $this->createJsonResponse($members);
    }

    public function memberAddAction(Request $request, $signInId)
    {
        $currentUser = $this->getCurrentuser();

        $userId = $request->request->get('userId');
        $signIn = $this->getSignInService()->getSignIn($signInId);
        $fields = array(
            'lessonId' => $signIn['lessonId'],
            'time' => $signIn['time'],
            'signinId' => $signIn['id'],
            'userId' => $userId,
            'courseId' => $signIn['courseId']
        );

        $member = $this->getSignInService()->createSignInMember($fields);

        return $this->forward('CustomBundle:Course/SignIn:setSignInStatus', array(
            'signInMemberId' => $member['id'],
            'status' => 'attend',
        ));
    }

    public function attendCountAction(Request $request)
    {
        $signInId = $request->query->get('signInId');
        $conditons = array(
            'signinId' => $signInId,
            'status' => 'attend',
        );

        $count = $this->getSignInService()->countSignInMembers($conditons);

        return $this->createJsonResponse($count);
    }

    public function deleteSignMemberAction($id)
    {
        $result = $this->getSignInService()->deleteSignMember($id);

        return $this->createJsonResponse(true);
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
