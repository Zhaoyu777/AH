<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Activity\ActivityActionInterface;

class RollcallController extends BaseController
{
    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/rollcall/preview.html.twig', array(
            'course' => $course,
            'task' => $task,
            'activity' => $activity,
        ));
    }

    public function showAction(Request $request, $activity, $task, $mode)
    {
        $results = $this->getRollcallResultService()->findResultsByTaskId($task['id']);
        $userIds = ArrayToolkit::column($results, 'userId');
        $results = ArrayToolkit::index($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $students = array();
        foreach ($userIds as $userId) {
            $user = $users[$userId];
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $students[] = array(
                'id' => $userId,
                'truename' => $user['truename'],
                'nickname' => $user['number'],
                'avatar' => $avatar,
            );
        }

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);

        return $this->render('activity/rollcall/show.html.twig', array(
            'activity' => $activity,
            'students' => $students,
            'results' => $results,
            'task' => $task,
            'lesson' => $lesson,
            'course' => $course,
            'mode' => $mode,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('rollcall');
        $rollcall = $config->get($activity['mediaId']);

        return $this->render('activity/rollcall/modal.html.twig', array(
            'activity' => $activity,
            'rollcall' => $rollcall,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/rollcall/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/discuss/finish-condition.html.twig', array());
    }

    public function remarkAction(Request $request, $resultId)
    {
        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();
            $result = $this->getRollcallResultService()->remarkResult($resultId, $fields);

            return $this->createJsonResponse(true);
        }
        $result = $this->getRollcallResultService()->getResult($resultId);
        $user = $this->getUserService()->getUser($result['userId']);

        return $this->render('activity/remark.html.twig', array(
            'remarkPath' => 'rollcall_result_remark',
            'result' => $result,
            'user' => $user,
        ));
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getRollcallResultService()
    {
        return $this->createService('CustomBundle:Activity:RollcallResultService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }
}
