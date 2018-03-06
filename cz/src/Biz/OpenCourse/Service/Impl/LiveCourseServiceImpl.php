<?php

namespace Biz\OpenCourse\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\AthenaLiveToolkit;
use Biz\BaseService;
use Biz\OpenCourse\Service\LiveCourseService;
use Biz\Util\EdusohoLiveClient;
use Topxia\Service\Common\ServiceKernel;

class LiveCourseServiceImpl extends BaseService implements LiveCourseService
{
    private $liveClient = null;

    public function createLiveRoom($course, $lesson, $container)
    {
        $liveParams = $this->_filterParams($course['teacherIds'], $lesson, $container, 'add');

        $live = $this->createLiveClient()->createLive($liveParams);

        if (empty($live)) {
            throw $this->createServiceException('Create liveroom failed, please try again');
        }

        if (isset($live['error'])) {
            throw $this->createServiceException($live['error']);
        }

        return $live;
    }

    public function editLiveRoom($course, $lesson, $container)
    {
        $liveParams = $this->_filterParams($course['teacherIds'], $lesson, $container, 'update');

        return $this->createLiveClient()->updateLive($liveParams);
    }

    public function entryLive($params)
    {
        //$lesson = $this->getOpenCourseService()->getLesson($lessonId);
        return $this->createLiveClient()->entryLive($params);
    }

    public function checkLessonStatus($lesson)
    {
        if (empty($lesson)) {
            return array('result' => false, 'message' => '课时不存在！');
            //throw $this->createServiceException("课时不存在！");
        }

        if (empty($lesson['mediaId'])) {
            return array('result' => false, 'message' => '直播教室不存在！');
            //throw $this->createServiceException("直播教室不存在！");
        }

        if ($lesson['startTime'] - time() > 7200) {
            return array('result' => false, 'message' => '直播还没开始!');
            //throw $this->createServiceException("直播还没开始!");
        }

        if ($lesson['endTime'] < time()) {
            return array('result' => false, 'message' => '直播已结束!');
            //throw $this->createServiceException("直播已结束!");
        }

        return array('result' => true, 'message' => '');
    }

    public function checkCourseUserRole($course, $lesson)
    {
        $role = '';
        $user = $this->getCurrentUser();

        if (!$user->isLogin() && $lesson['type'] == 'liveOpen') {
            return 'student';
        } elseif (!$user->isLogin() && $lesson['type'] != 'liveOpen') {
            throw $this->createServiceException('您还未登录，不能参加直播！');
        }

        $courseMember = $this->getOpenCourseService()->getCourseMember($lesson['courseId'], $user['id']);

        if (!$courseMember) {
            throw $this->createServiceException('您不是课程学员，不能参加直播！');
        }

        $role = 'student';
        $courseTeachers = $this->getOpenCourseService()->findCourseTeachers($lesson['courseId']);
        $courseTeachersIds = ArrayToolkit::column($courseTeachers, 'userId');
        $courseTeachers = ArrayToolkit::index($courseTeachers, 'userId');

        if (in_array($user['id'], $courseTeachersIds)) {
            $teacherId = array_shift($course['teacherIds']);
            $firstTeacher = $courseTeachers[$teacherId];
            if ($firstTeacher['userId'] == $user['id']) {
                $role = 'teacher';
            } else {
                $role = 'speaker';
            }
        }

        return $role;
    }

    public function findBeginingLiveCourse($afterSecond)
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser->isLogin()) {
            return array();
        }

        $lessons = $this->getLessonDao()->findBeginningLiveCoures($afterSecond, 10);

        foreach ($lessons as $key => $lesson) {
            $member = $this->getCourseMemberService()->getCourseMember($lesson['courseId'], $currentUser['id']);
            if (!empty($member)) {
                $lesson['course'] = $this->getCourseService()->getCourse($lesson['courseId']);
                $teacherMembers = $this->getCourseMemberService()->findCourseTeachers($lesson['courseId']);
                $teacherIds = ArrayToolkit::column($teacherMembers, 'userId');
                $lesson['teachers'] = $this->getUserService()->findUsersByIds($teacherIds);

                return $lesson;
            }
        }

        return array();
    }

    /**
     * only for mock.
     *
     * @param [type] $liveClient [description]
     */
    public function setLiveClient($liveClient)
    {
        return $this->liveClient = $liveClient;
    }

    protected function createLiveClient()
    {
        if (empty($this->liveClient)) {
            $this->liveClient = new EdusohoLiveClient();
        }

        return $this->liveClient;
    }

    private function _getSpeaker($courseTeachers)
    {
        $speakerId = current($courseTeachers);
        $speaker = $speakerId ? $this->getUserService()->getUser($speakerId) : null;

        return $speaker ? $speaker['nickname'] : '老师';
    }

    private function _filterParams($courseTeacherIds, $lesson, $container, $actionType = 'add')
    {
        $params = array(
            'summary' => isset($lesson['summary']) ? $lesson['summary'] : '',
            'title' => $lesson['title'],
            'type' => $lesson['type'],
            'speaker' => $this->_getSpeaker($courseTeacherIds),
            'authUrl' => $container->get('router')->generate('live_auth', array(), true),
            'jumpUrl' => $container->get('router')->generate('live_jump', array('id' => $lesson['courseId']), true),
            'callback' => $this->buildCallbackUrl($lesson),
        );

        if ($actionType == 'add') {
            $params['liveLogoUrl'] = $this->_getLiveLogo();
            $params['startTime'] = $lesson['startTime'].'';
            $params['endTime'] = ($lesson['startTime'] + $lesson['length'] * 60).'';
        } elseif ($actionType == 'update') {
            $params['liveId'] = $lesson['mediaId'];
            $params['provider'] = $lesson['liveProvider'];

            if (isset($lesson['startTime']) && !empty($lesson['startTime'])) {
                $params['startTime'] = $lesson['startTime'];

                if (isset($lesson['length']) && !empty($lesson['length'])) {
                    $params['endTime'] = ($lesson['startTime'] + $lesson['length'] * 60).'';
                }
            }
        }

        return $params;
    }

    private function _getLiveLogo()
    {
        $liveLogo = $this->getSettingService()->get('course');
        $liveLogoUrl = '';

        if (!empty($liveLogo) && isset($liveLogo['live_logo']) && !empty($liveLogo['live_logo'])) {
            $liveLogoUrl = ServiceKernel::instance()->getEnvVariable('baseUrl').'/'.$liveLogo['live_logo'];
        }

        return $liveLogoUrl;
    }

    protected function buildCallbackUrl($lesson)
    {
        $baseUrl = $this->biz['env']['base_url'];

        $duration = $lesson['startTime'] + $lesson['length'] * 60 + 86400 - time();
        $args = array(
            'duration' => $duration,
            'data' => array(
                'courseId' => $lesson['courseId'],
                'type' => 'open_course',
            ),
        );
        $token = $this->getTokenService()->makeToken('live.callback', $args);

        return AthenaLiveToolkit::generateCallback($baseUrl, $token['token'], $lesson['courseId']);
    }

    protected function getOpenCourseService()
    {
        return $this->createService('OpenCourse:OpenCourseService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    protected function getLessonDao()
    {
        return $this->createDao('OpenCourse:OpenCourseLessonDao');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function getLiveReplayService()
    {
        return $this->createService('Course:LiveReplayService');
    }

    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }
}
