<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Activity\ActivityActionInterface;

class RandomTestpaperController extends BaseController
{
    public function submitResultAction(Request $request, $taskId)
    {
        $fields = $request->request->all();
        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();
        $fields['data'] = json_decode($fields['data'], true);

        if (empty($fields['questionIds'])) {
            $fields['questionIds'] = array();
        }

        $builder->createTestpaper($taskId, $fields['questionIds'], $fields['data']);

        return $this->createJsonResponse(true);
    }

    public function makeResultData($task, $activityId, $testpaperId)
    {
        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();
        $activity = $this->getActivityService()->getActivity($activityId);
        $questions = $builder->showTestItems($testpaperId);
        $totals = $this->makeTestpaperTotal($questions);

        $config = $this->getActivityService()->getActivityConfig('randomTestpaper');
        $randomTestpaper = $config->get($activity['mediaId']);

        $course = $this->getCourseService()->getCourse($task['courseId']);

        return array(
            'activity' => $activity,
            'questions' => $questions,
            'realScore' => $totals['realScore'],
            'totals' => $totals['total'],
            'randomTestpaper' => $randomTestpaper,
            'taskId' => $task['id'],
            'task' => $task,
            'course' => $course
        );
    }

    public function makeTestpaperTotal($questions)
    {
        $total = array();
        $realScore = 0;
        $totalItem = 0;

        $types = array(
            'single_choice',
            'choice',
            'fill',
            'uncertain_choice',
            'determine'
        );
        foreach ($types as $type) {
            $itemCount = 0;
            $totalScore = 0;
            $status = array(
                'right' => 0,
                'wrong' => 0,
                'noAnswer' => 0,
                'partRight' => 0
            );
            if (!empty($questions[$type])) {
                foreach ($questions[$type] as $question) {
                    $totalScore += (int)$question['realScore'];
                    if ($question['testResult']['status'] == null) {
                        $question['testResult']['status'] = 'noAnswer';
                    }
                    $status[$question['testResult']['status']]++;
                    $itemCount++;
                }
            }
            $total[$type] = array(
                'itemCount' => $itemCount,
                'totalScore' => $totalScore,
                'status' => $status
            );
            $realScore += $totalScore;
            $totalItem += $itemCount;
        }

        return array('total' => $total, 'realScore' => $realScore, 'totalItem' => $totalItem);
    }

    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();
        $questions = $builder->buildItems($activity['id']);

        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/random-testpaper/preview.html.twig', array(
            'course' => $course,
            'activity' => $activity,
            'questions' => $questions,
            'task' => $task,
        ));
    }

    public function showAction(Request $request, $activity, $task, $doAgain)
    {
        $user = $this->getCurrentUser();
        $testpaper = $this->getRandomTestpaperService()->getLastTestpaperByTaskIdAndUserId($task['id'], $user['id']);
        if (!((empty($testpaper) || $doAgain))) {
            $result = $this->makeResultData($task, $task['activityId'], $testpaper['id']);

            return $this->render('activity/random-testpaper/show-result.html.twig', $result);
        }

        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();
        $questions = $builder->buildItems($activity['id']);

        $config = $this->getActivityService()->getActivityConfig('randomTestpaper');
        $randomTestpaper = $config->get($activity['mediaId']);

        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/random-testpaper/show.html.twig', array(
            'activity' => $activity,
            'questions' => $questions,
            'randomTestpaper' => $randomTestpaper,
            'task' => $task,
            'course' => $course,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $types = $this->getQuestionTypes();
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('randomTestpaper');
        $randomTestpaper = $config->get($activity['mediaId']);

        $course = $this->getCourseService()->getCourse($courseId);

        $questionNums = $this->getQuestionService()->getQuestionCountGroupByTypes(array('courseId' => $randomTestpaper['metas']['range']['courseId'], 'lessonId' => $randomTestpaper['metas']['range']['lessonId'], 'difficulty' => $randomTestpaper['metas']['difficulty']));
        $questionNums = ArrayToolkit::index($questionNums, 'type');

        return $this->render('activity/random-testpaper/modal.html.twig', array(
            'activity' => $activity,
            'randomTestpaper' => $randomTestpaper,
            'courseId' => $courseId,
            'course' => $course,
            'courseSetId' => $course['courseSetId'],
            'questionNums' => $questionNums,
            'types' => $types,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        $types = $this->getQuestionTypes();

        $course = $this->getCourseService()->getCourse($courseId);
        $tasks = $this->getTaskService()->findTasksByCourseId($courseId);

        $questionNums = $this->getQuestionService()->getQuestionCountGroupByTypes(array('courseSetId' => $course['courseSetId']));
        $questionNums = ArrayToolkit::index($questionNums, 'type');

        return $this->render('activity/random-testpaper/modal.html.twig', array(
            'courseId' => $courseId,
            'types' => $types,
            'courseSetId' => $course['courseSetId'],
            'course' => $course,
            'tasks' => $tasks,
            'questionNums' => $questionNums,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/random-testpaper/finish-condition.html.twig', array());
    }

    protected function getQuestionTypes()
    {
        $typesConfig = $this->get('extension.manager')->getQuestionTypes();

        $types = array();
        foreach ($typesConfig as $type => $typeConfig) {
            if ($type != 'essay' and $type != 'material') {
                $types[$type] = array(
                    'name' => $typeConfig['name'],
                    'hasMissScore' => $typeConfig['hasMissScore'],
                );
            }
        }

        return $types;
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
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

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getQuestionService()
    {
        return $this->createService('Question:QuestionService');
    }

    protected function getRandomTestpaperService()
    {
        return $this->createService('CustomBundle:RandomTestpaper:RandomTestpaperService');
    }
}
