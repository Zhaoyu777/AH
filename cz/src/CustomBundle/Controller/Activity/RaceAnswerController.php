<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Activity\ActivityActionInterface;

class RaceAnswerController extends BaseController
{
    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/race-answer/preview.html.twig', array(
            'course' => $course,
            'task' => $task,
            'activity' => $activity,
        ));
    }

    public function showAction(Request $request, $activity, $task)
    {
        $results = $this->getRaceAnswerService()->findResultByTaskId($task['id']);
        $userIds = ArrayToolkit::column($results, 'userId');
        $results = ArrayToolkit::index($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

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

        return $this->render('activity/race-answer/show.html.twig', array(
            'activity' => $activity,
            'students' => $students,
            'results' => $results,
            'task' => $task,
            'lesson' => $lesson,
            'course' => $course,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('rollcall');
        $rollcall = $config->get($activity['mediaId']);

        return $this->render('activity/race-answer/modal.html.twig', array(
            'activity' => $activity,
            'rollcall' => $rollcall,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/race-answer/modal.html.twig', array(
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
            $result = $this->getRaceAnswerService()->remarkResult($resultId, $fields);

            return $this->createJsonResponse(true);
        }
        $result = $this->getRaceAnswerService()->getResult($resultId);
        $user = $this->getUserService()->getUser($result['userId']);

        return $this->render('activity/remark.html.twig', array(
            'remarkPath' => 'race_answer_result_remark',
            'result' => $result,
            'user' => $user,
        ));
    }

    public function loadResultAction($taskId)
    {
        $results = $this->getRaceAnswerService()->findResultByTaskId($taskId);
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
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        return $this->render('activity/race-answer/result-tr.html.twig', array(
            'results' => $results,
            'students' => $students,
            'lesson' => $lesson,
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

    protected function getRaceAnswerService()
    {
        return $this->createService('CustomBundle:RaceAnswer:RaceAnswerService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
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
