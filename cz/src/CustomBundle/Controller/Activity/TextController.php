<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseDraftService;
use Symfony\Component\HttpFoundation\Request;

class TextController extends BaseController
{
    public function showAction(Request $request, $activity, $task)
    {
        if (empty($activity)) {
            throw $this->createNotFoundException('activity not found');
        }

        $text = $this->getActivityService()->getActivityConfig('text')->get($activity['mediaId']);

        if (empty($text)) {
            throw $this->createNotFoundException('text activity not found');
        }
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/text/show.html.twig', array(
            'activity' => $activity,
            'text' => $text,
            'task' => $task,
            'course' => $course,
        ));
    }

    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);

        if (empty($activity)) {
            throw $this->createNotFoundException('activity not found');
        }

        $text = $this->getActivityService()->getActivityConfig('text')->get($activity['mediaId']);

        if (empty($text)) {
            throw $this->createNotFoundException('text activity not found');
        }

        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/text/preview.html.twig', array(
            'activity' => $activity,
            'text' => $text,
            'task' => $task,
            'course' => $course,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $user = $this->getCurrentUser();
        $activity = $this->getActivityService()->getActivity($id);
        $text = $this->getActivityService()->getActivityConfig('text')->get($activity['mediaId']);
        $draft = $this->getCourseDraftService()->getCourseDraftByCourseIdAndActivityIdAndUserId($courseId, $activity['id'], $user->id);

        return $this->render('activity/text/modal.html.twig', array(
            'activity' => $activity,
            'text' => $text,
            'courseId' => $courseId,
            'draft' => $draft,
        ));
    }

    public function autoSaveAction(Request $request, $courseId, $activityId = 0)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user not login');
        }

        $content = $request->request->get('content', '');

        if (empty($content)) {
            return $this->createJsonResponse(true);
        }

        $draft = $this->getCourseDraftService()->getCourseDraftByCourseIdAndActivityIdAndUserId($courseId, $activityId,
            $user->getId());

        if (empty($draft)) {
            $draft = array(
                'activityId' => $activityId,
                'title' => '',
                'content' => $content,
                'courseId' => $courseId,
            );

            $this->getCourseDraftService()->createCourseDraft($draft);
        } else {
            $draft['content'] = $content;
            $this->getCourseDraftService()->updateCourseDraft($draft['id'], $draft);
        }

        return $this->createJsonResponse(true);
    }

    public function createAction(Request $request, $courseId)
    {
        $user = $this->getCurrentUser();
        $draft = $this->getCourseDraftService()->getCourseDraftByCourseIdAndActivityIdAndUserId($courseId, 0, $user->id);

        return $this->render('activity/text/modal.html.twig', array(
            'courseId' => $courseId,
            'draft' => $draft,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        $media = $this->getActivityService()->getActivityConfig('text')->get($activity['mediaId']);

        return $this->render('activity/text/finish-condition.html.twig', array(
            'media' => $media,
        ));
    }

    /**
     * @return CourseDraftService
     */
    public function getCourseDraftService()
    {
        return $this->createService('Course:CourseDraftService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }
}
