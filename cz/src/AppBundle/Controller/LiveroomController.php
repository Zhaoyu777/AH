<?php

namespace AppBundle\Controller;

use Biz\Accessor\AccessorInterface;
use Biz\Activity\Service\ActivityService;
use Biz\Task\Service\TaskService;
use Biz\Course\Service\CourseService;
use Biz\CloudPlatform\CloudAPIFactory;
use Biz\Course\Service\LiveReplayService;
use Biz\OpenCourse\Service\OpenCourseService;
use Symfony\Component\HttpFoundation\Request;

class LiveroomController extends BaseController
{
    const LIVE_OPEN_COURSE_TYPE = 'open_course';
    const LIVE_COURSE_TYPE = 'course';

    public function _entryAction(Request $request, $roomId, $params = array())
    {
        $user = $request->query->all();
        $user['device'] = $this->getDevice($request);

        if ($request->isSecure()) {
            $user['protocol'] = 'https';
        }

        $systemUser = $this->getUserService()->getUser($user['id']);
        $avatar = !empty($systemUser['smallAvatar']) ? $systemUser['smallAvatar'] : '';
        $avatar = $this->getWebExtension()->getFurl($avatar, 'avatar.png');
        $user['avatar'] = $avatar;

        $biz = $this->getBiz();
        $user['hostname'] = $biz['env']['base_url'];

        $ticket = CloudAPIFactory::create('leaf')->post("/liverooms/{$roomId}/tickets", $user);

        return $this->render('liveroom/entry.html.twig', array(
            'roomId' => $roomId,
            'params' => $params,
            'ticket' => $ticket,
        ));
    }

    /**
     * [playESLiveReplayAction 播放ES直播回放].
     */
    public function playESLiveReplayAction(Request $request, $targetType, $targetId, $lessonId, $replayId)
    {
        $replay = $this->getLiveReplayService()->getReplay($replayId);
        if (empty($replay)) {
            throw $this->createNotFoundException();
        }

        if ($this->canTakeReplay($targetType, $targetId, $lessonId, $replayId)) {
            return $this->forward('AppBundle:MaterialLib/GlobalFilePlayer:player', array('globalId' => $replay['globalId']));
        }

        throw $this->createNotFoundException();
    }

    public function ticketAction(Request $request, $roomId)
    {
        $ticketNo = $request->query->get('ticket');
        $ticket = CloudAPIFactory::create('leaf')->get("/liverooms/{$roomId}/tickets/{$ticketNo}");

        return $this->createJsonResponse($ticket);
    }

    protected function canTakeOpenCourseReplay($courseId, $replayId)
    {
        $openCourse = $this->getOpenCourseService()->getCourse($courseId);
        $replay = $this->getLiveReplayService()->getReplay($replayId);

        return $openCourse['status'] == 'published' && $openCourse['id'] == $replay['courseId'] && $openCourse['type'] == $replay['type'];
    }

    protected function canTakeCourseReplay($courseId, $activityId, $replayId)
    {
        $task = $this->getTaskService()->getTaskByCourseIdAndActivityId($courseId, $activityId);
        if (!$task) {
            throw $this->createNotFoundException();
        }
        $access = $this->getCourseService()->canLearnTask($task['id']);

        return $access['code'] == AccessorInterface::SUCCESS;
    }

    protected function canTakeReplay($targetType, $targetId, $lessonId, $replayId)
    {
        if ($targetType === self::LIVE_OPEN_COURSE_TYPE) {
            return $this->canTakeOpenCourseReplay($targetId, $replayId);
        } elseif ($targetType === self::LIVE_COURSE_TYPE) {
            return $this->canTakeCourseReplay($targetId, $lessonId, $replayId);
        }

        return false;
    }

    protected function getDevice($request)
    {
        if ($this->isMobileClient()) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }

    /**
     * @return OpenCourseService
     */
    protected function getOpenCourseService()
    {
        return $this->createService('OpenCourse:OpenCourseService');
    }

    /**
     * @return LiveReplayService
     */
    protected function getLiveReplayService()
    {
        return $this->createService('Course:LiveReplayService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getWebExtension()
    {
        return $this->container->get('web.twig.extension');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }
}
