<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class QuestionnaireController extends BaseController
{
    public function doQuestionnaireAction(Request $request, $taskId, $questionnaireId)
    {
        $user = $this->getUser();
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireId);

        if (empty($questionnaire)) {
            throw $this->createResourceNotFoundException('questionnaire', $questionnaireId);
        }

        $task = $this->getTaskService()->getTask($taskId);

        $questionnaireResult = $this->getQuestionnaireService()->startQuestionnaire($questionnaire['id'], array('taskId' => $task['id'], 'activityId' => $task['activityId']));

        if ('doing' === $questionnaireResult['status']) {
            return $this->redirect($this->generateUrl('questionnaire_show', array('resultId' => $questionnaireResult['id'])));
        }

        return $this->redirect(
            $this->generateUrl('questionnaire_result_show', array('questionnaireId' => $questionnaireResult['questionnaireId'], 'taskId' => $taskId))
        );
    }

    public function showAction(Request $request, $activity, $task, $mode, $preview = 0)
    {
        $user = $this->getCurrentUser();

        if ($preview) {
            return $this->previewQuestionnaire($activity['id'], $task);
        }
        $config = $this->getActivityService()->getActivityConfig('questionnaire');
        $questionnaireActivity = $config->get($activity['mediaId']);
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireActivity['mediaId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if (!$questionnaire) {
            return $this->render('activity/questionnaire/preview.html.twig', array(
                'questionnaire' => null,
                'task' => $task,
                'mode' => $mode,
                'isDone' => true,
                'course' => $course,
                'lesson' => $lesson,
            ));
        }
        $questionnaireResult = $this->getQuestionnaireService()->getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaire['id'], $task['id'], $user['id']);
        $taskStatus = $this->getTaskStatusService()->getStatusByTaskId($task['id']);

        if (($lesson['status'] == 'teached' && $lessonTask['stage'] == 'in') || $questionnaireResult && $questionnaireResult['status'] == 'finished') {
            return $this->redirect(
                $this->generateUrl('questionnaire_result_show', array('questionnaireId' => $questionnaire['id'], 'taskId' => $task['id'], 'mode' => $mode))
            );
        }

        $member = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);
        if ($mode == 'report' || $member['role'] == 'teacher') {
            return $this->redirect(
                $this->generateUrl('questionnaire_result_show', array('questionnaireId' => $questionnaire['id'], 'taskId' => $task['id'], 'mode' => $mode))
            );
        }

        return $this->render('activity/questionnaire/show.html.twig', array(
            'questionnaire' => $questionnaire,
            'lesson' => $lesson,
            'lessonTask' => $lessonTask,
            'task' => $task,
            'course' => $course,
            'mode' => $mode,
            'role' => $member['role'],
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $questionnaires = $this->getQuestionnaireService()->findQuestionnairesNotNullByCourseSetId($course['courseSetId']);

        return $this->render('activity/questionnaire/modal.html.twig', array(
            'courseId' => $courseId,
            'course' => $course,
            'questionnaires' => $questionnaires,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $questionnaires = $this->getQuestionnaireService()->findQuestionnairesNotNullByCourseSetId($courseSet['id']);

        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('questionnaire');
        $questionnaireActivity = $config->get($activity['mediaId']);

        if ($questionnaireActivity) {
            $activity['questionnaireMediaId'] = $questionnaireActivity['mediaId'];
        }

        return $this->render('activity/questionnaire/modal.html.twig', array(
            'activity' => $activity,
            'questionnaires' => $questionnaires,
            'course' => $course,
        ));
    }

    public function previewAction($task)
    {
        return $this->previewQuestionnaire($task['activityId'], $task);
    }

    public function previewQuestionnaire($id, $task)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('questionnaire');
        $questionnaireActivity = $config->get($activity['mediaId']);
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireActivity['mediaId']);
        $course = $this->getCourseService()->getCourse($activity['fromCourseId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if (empty($questionnaire)) {
            return $this->render('activity/questionnaire/preview.html.twig', array(
                'questionnaire' => null,
                'task' => $task,
                'course' => $course,
                'lesson' => $lesson,
            ));
        }

        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($questionnaire['id'], 0, PHP_INT_MAX);

        return $this->render('activity/questionnaire/preview.html.twig', array(
            'questions' => $questions,
            'task' => $task,
            'course' => $course,
            'activity' => $activity,
            'questionnaire' => $questionnaire,
        ));
    }

    public function doTestAction($resultId)
    {
        $result = $this->getQuestionnaireService()->getQuestionnaireResult($resultId);
        $task = $this->getTaskService()->getTask($result['taskId']);
        $activity = $this->getActivityService()->getActivity($result['activityId']);
        $config = $this->getActivityService()->getActivityConfig('questionnaire');
        $questionnaireActivity = $config->get($activity['mediaId']);
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireActivity['mediaId']);

        if (empty($questionnaire)) {
            throw $this->createResourceNotFoundException('questionnaire', $questionnaireId);
        }
        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($questionnaire['id'], 0, PHP_INT_MAX);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/questionnaire/start-do-show.html.twig', array(
            'questions' => $questions,
            'questionnaire' => $questionnaire,
            'total' => count($questions),
            'result' => $result,
            'task' => $task,
            'course' => $course,
        ));
    }

    public function finishTestAction(Request $request, $resultId)
    {
        if ($request->getMethod() === 'POST') {
            $formData = $request->request->all();
            $formData['data'] = json_decode($formData['data'], true);

            $result = $this->getQuestionnaireService()->finishTest($resultId, $formData);

            $response = array('result' => true, 'message' => '');

            return $this->createJsonResponse($response);
        }
    }

    public function showResultAction(Request $request, $taskId, $questionnaireId)
    {
        $mode = $request->query->get('mode', 'show');

        return $this->showQuestionnaireResult($taskId, $questionnaireId, $mode);
    }

    public function resultLoadAction($taskId, $questionnaireId, $mode)
    {
        return $this->showQuestionnaireResult($taskId, $questionnaireId, $mode, true);
    }

    protected function showQuestionnaireResult($taskId, $questionnaireId, $mode, $load = false)
    {
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireId);

        if (empty($questionnaire)) {
            throw $this->createResourceNotFoundException('questionnaire', $questionnaireId);
        }

        $canLookQuestionnaire = $this->getQuestionnaireService()->canLookQuestionnaireResults($taskId, $questionnaireId);

        $questionResults = $this->getQuestionnaireService()->findQuestionResultsByQuestionnaireIdAndTaskId($questionnaireId, $taskId);

        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        // if (empty($questionResults)) {
        //     return $this->render($this->getResultTemplate($load), array(
        //         'questionResults' => null,
        //         'taskId' => $taskId,
        //         'questionnaire' => $questionnaire,
        //         'lesson' => $lesson,
        //     ));
        // }

        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireIdAndTaskIdAndStatus($questionnaireId, $taskId, 'finished');

        $memberNum = $this->getCourseMemberService()->countMembers(array('courseId' => $task['courseId'], 'role' => 'student'));
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render($this->getResultTemplate($load), array(
            'questionResults' => $questionResults,
            'questionnaire' => $questionnaire,
            'actualNum' => count($questionnaireResults),
            'memberNum' => $memberNum,
            'taskId' => $task['id'],
            'task' => $task,
            'lesson' => $lesson,
            'activity' => $activity,
            'lessonTask' => $lessonTask,
            'course' => $course,
            'mode' => $mode,
        ));
    }

    public function fetchQuestionnaireResultsAction(Request $request, $taskId, $questionnaireId)
    {
        $questionResults = $this->getQuestionnaireService()->findQuestionResultsByQuestionnaireIdAndTaskId($questionnaireId, $taskId);
        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireIdAndTaskIdAndStatus($questionnaireId, $taskId, 'finished');

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);

        return $this->createJsonResponse(array(
            'questionResults' => $questionResults,
            'actualNum' => count($questionnaireResults),
        ));
    }

    protected function findQuestionResultsByQuestionnaireId($questionnaireId)
    {
        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($questionnaireId, 0, PHP_INT_MAX);
        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, 'finished');
        $questionnaireResults = ArrayToolkit::index($questionnaireResults, 'id');

        $userIds = ArrayToolkit::column($questionnaireResults, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $questionnaireResultIds = ArrayToolkit::column($questionnaireResults, 'id');
        $userAnswers = $this->getQuestionService()->findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);

        if (!empty($userAnswers)) {
            $userAnswers = ArrayToolkit::group($userAnswers, 'questionId');
        }

        foreach ($questions as &$question) {
            $choices = array();
            $answers = empty($userAnswers[$question['id']]) ? array() : $userAnswers[$question['id']];
            if (in_array($question['type'], array('single_choice', 'choice'))) {
                foreach ($answers as $answer) {
                    $choices = array_merge_recursive($choices, $answer['answer']) ;
                }
                $itemCount = array_count_values($choices);

                $items = array();
                foreach ($question['metas'] as $key => $value) {
                    $items[$key]['text'] = $value;
                    $items[$key]['num'] = empty($itemCount[$key]) ? 0 : $itemCount[$key];
                    $items[$key]['part'] = empty($questionnaireResults) ? 0 : round($items[$key]['num']/count($questionnaireResults)*100, 2);
                }
                $question['items'] = $items;
                unset($question['metas']);
            } else {
                foreach ($answers as $answer) {
                    if (empty($answer['answer'][0])) {
                        continue;
                    }
                    $questionnaireResult = $questionnaireResults[$answer['questionnaireResultId']];
                    $userId = $questionnaireResult['userId'];
                    $question['answers'][$userId]['content'] = $answer['answer'][0];
                    $question['answers'][$userId]['user'] = $users[$userId]['nickname'];
                }
            }
        }
        return $questions;
    }

    protected function getResultTemplate($load)
    {

        if ($load) {
            return 'activity/questionnaire/result-content.html.twig';
        }

        return 'activity/questionnaire/result.html.twig';
    }

    protected function getUserService()
    {
        return $this->createService('CustomBundle:User:UserService');
    }

    protected function getQuestionnaireService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }
}
