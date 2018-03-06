<?php

namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class SignInController extends BaseController
{
    public function startAction(Request $request, $lessonId)
    {
        $signIn = $this->getSignInService()->getLastSignInByLessonId($lessonId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        $signIns = $this->getSignInService()->findIngSignInByCourseId($lesson['courseId']);
        foreach ($signIns as $key => $signIn) {
            if (($signIn['createdTime'] + 45 * 60) < time() && ($signIn['status'] != 'end')) {
                $this->getSignInService()->endSignIn($signIn['id']);
                unset($signIns[$key]);
            }
        }
        if (!empty($signIns)) {
            return $this->createJsonResponse(array(
                'message' => '该班级已开始其他签到',
            ));
        }
        $code = $this->generateSignCode(4);
        $signIn = array(
            'time' => empty($signIn) ? 1 : 2,
            'verifyCode' => $code,
            'lessonId' => $lessonId,
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

    public function cancelAction($signInId)
    {
        $this->getSignInService()->tryManageSignIn($signInId);
        $this->getSignInService()->cancelSignIn($signInId);

        return $this->createJsonResponse(true);
    }

    public function signInStatusAction($lessonId)
    {
        $signIn = $this->getSignInService()->getLastSignInByLessonId($lessonId);
        $result = array(
            'status' => $signIn['status'],
            'time' => empty($signIn['time']) ? 0 : $signIn['time']
        );

        return $this->createJsonResponse($result);
    }

    public function endAction($signInId)
    {
        $this->getSignInService()->tryManageSignIn($signInId);
        $signIn = $this->getSignInService()->endSignIn($signInId);

        return $this->createJsonResponse(array(
            'status' => $signIn['status'],
        ));
    }

    public function resultAction($lessonId, $time)
    {
        $signIn = $this->getSignInService()->getSignInByLessonIdAndTime($lessonId, $time);

        $members = $this->getSignInService()->findSignInMembersBySignInId($signIn['id']);
        $memberCount = count($members);
        $userIds = ArrayToolkit::column($members, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        array_walk(
            $members,
            function (&$member) use ($users) {
                $avatar = $this->get('web.twig.app_extension')->userAvatar($users[$member['userId']], 'small');
                $member['avatar'] = $this->getWebExtension()->getFpath($avatar, 'avatar.png');
                $member['nickname'] = $users[$member['userId']]['nickname'];
            }
        );
        $members = ArrayToolkit::group($members, 'status');
        $default = array(
            'attend' => array(),
            'absent' => array(),
            'late' => array(),
            'leave' => array(),
            'early' => array(),
        );
        $members = array_merge($default, $members);
        $result = array(
            'memberCount' => $memberCount,
            'attendCount' => count($members['attend']),
            'members' => $members,
        );

        return $this->createJsonResponse($result);
    }

    public function recordAction($courseId)
    {
        $user = $this->getCurrentUser();
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);
        $lessonIds = ArrayToolkit::column($lessons, 'id');
        $records = $this->getSignInService()->findSignInMembersByUserIdAndLessonIds($user['id'], $lessonIds);

        $result = array();
        foreach ($records as $key => $record) {
            if ($record['status'] == 'attend') {
                $result[$key]['lesson'] = '课次'.$lessons[$record['lessonId']]['number'];
                $result[$key]['updatedTime'] = $record['updatedTime'];
                $result[$key]['address'] = $record['address'];
            }
        }

        return $this->createJsonResponse($result);
    }

    public function studentSignInAction(Request $request, $lessonId, $time)
    {
        $currentUser = $this->getCurrentuser();
        $fields = $request->query->all();

        $this->getSignInService()->studentSignIn($currentUser['id'], $lessonId, $time, $fields);

        return $this->createJsonResponse(true);
    }

    public function studentSignInStatusAction($lessonId, $time)
    {
        $user = $this->getCurrentuser();
        $signIn = $this->getSignInService()->getSignInByLessonIdAndTime($lessonId, $time);
        $member = $this->getSignInService()->getSignInMemberBySignInIdAndUserId($signIn['id'], $user['id']);

        return $this->createJsonResponse(array('status' => $member['status']));
    }

    public function detailAction($lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $signIn = $this->getSignInService()->getLastSignInByLessonId($lesson['id']);

        return $this->createJsonResponse(array(
            'lessonTitle' => '课次'.$lesson['number'].'：'.$lesson['title'],
            'signIn' => array(
                'surplusTime' => 2 - $signIn['time'],
                'createdTime' => $signIn['createdTime'],
                'status' => $signIn['status'],
                'time' => $signIn['time'],
                'currentTime' => time(),
                'id' => $signIn['id'],
                'code' => $signIn['verifyCode'],
            ),
        ));
    }

    public function studentSignInSuccessAction($lessonId, $time)
    {
        $user = $this->getCurrentuser();
        $signIn = $this->getSignInService()->getSignInByLessonIdAndTime($lessonId, $time);
        $member = $this->getSignInService()->getSignInMemberBySignInIdAndUserId($signIn['id'], $user['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->createJsonResponse(array(
            'courseTitle' => $courseSet['title'],
            'lessonTitle' => '课次'.$lesson['number'].'：'.$lesson['title'],
            'updatedTime' => $member['updatedTime'],
        ));
    }

    public function setStatusAction(Request $request, $signInMemberId)
    {
        $status = $request->query->get('status');
        $currentUser = $this->getCurrentuser();
        $member = $this->getSignInService()->setSignInMemberStatus($signInMemberId, $status, $currentUser['id']);

        $signIn = $this->getSignInService()->getSignIn($member['signinId']);

        return $this->forward('CustomBundle:Weixin/SignIn:result', array('lessonId' => $member['lessonId'], 'time' => $signIn['time']));
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

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
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
