<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use Biz\Activity\Service\ActivityService;
use Symfony\Component\HttpFoundation\Request;

class AudioController extends BaseController
{
    public function showAction(Request $request, $activity, $task)
    {
        $audio = $this->getActivityService()->getActivityConfig($activity['mediaType'])->get($activity['mediaId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/audio/show.html.twig', array(
            'activity' => $activity,
            'audio' => $audio,
            'course' => $course,
            'task' => $task,
        ));
    }

    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId'], $fetchMedia = true);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/audio/preview.html.twig', array(
            'task' => $task,
            'course' => $course,
            'activity' => $activity,
            'courseId' => $task['courseId'],
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id, $fetchMedia = true);
        $activity = $this->fillMinuteAndSecond($activity);

        return $this->render('activity/audio/modal.html.twig', array(
            'activity' => $activity,
            'courseId' => $courseId,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/audio/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/audio/finish-condition.html.twig', array());
    }

    protected function fillMinuteAndSecond($activity)
    {
        if (!empty($activity['length'])) {
            $activity['minute'] = (int) ($activity['length'] / 60);
            $activity['second'] = (int) ($activity['length'] % 60);
        }

        return $activity;
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }
}
