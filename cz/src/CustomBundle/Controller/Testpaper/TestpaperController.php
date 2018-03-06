<?php

namespace CustomBundle\Controller\Testpaper;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class TestpaperController extends BaseController
{
    public function listAction(Request $request, $type)
    {
        if (!in_array($type, array('instant', 'other'))) {
            return $this->createMessageResponse('error', "不存在#{$type}类型");
        }

        $user = $this->getUser();
        if (!$user->isLogin()) {
            return $this->createMessageResponse('error', '请先登录！', '', 5, $this->generateUrl('login'));
        }

        $conditions = array(
            'userId' => $user['id'],
            'type' => 'testpaper',
            'courseType' => $type
        );

        $paginator = new Paginator(
            $request,
            $this->getTestpaperService()->searchTestpaperResultsCountByStatus($conditions),
            10
        );

        $paperResults = $this->getTestpaperService()->searchTestpaperResultsByStatus(
            $conditions,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $courseSetIds = ArrayToolkit::column($paperResults, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        $courseIds = ArrayToolkit::column($paperResults, 'courseId');
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);

        $testpaperIds = ArrayToolkit::column($paperResults, 'testId');
        $testpapers = $this->getTestpaperService()->findTestpapersByIds($testpaperIds);

        $activityIds = ArrayToolkit::column($paperResults, 'lessonId');
        $tasks = $this->getTaskService()->findTasksByActivityIds($activityIds);

        if ($type == 'instant') {
            $sideNav = 'my-instant-testpaper';
        } else {
            $sideNav = 'my-testpaper';
        }

        return $this->render('my/testpaper/my-testpaper-list.html.twig', array(
            'paperResults' => $paperResults,
            'paginator' => $paginator,
            'courses' => $courses,
            'courseSets' => $courseSets,
            'testpapers' => $testpapers,
            'tasks' => $tasks,
            'nav' => 'testpaper',
            'sideNav' => $sideNav,
        ));
    }

    public function finishTestAction(Request $request, $resultId)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

        if (empty($testpaperResult)) {
            return $this->createJsonResponse(array('result' => false, 'message' => '老师取消上课，不能提交试卷！'));
        }

        if (!empty($testpaperResult) && !in_array($testpaperResult['status'], array('doing', 'paused'))) {
            return $this->createJsonResponse(array('result' => false, 'message' => '试卷已提交，不能再修改答案！'));
        }

        $courseTask = $this->getTaskService()->getTaskByCourseIdAndActivityId($testpaperResult['courseId'], $testpaperResult['lessonId']);

        $taskStatus = $this->getTaskStatusService()->getStatusByTaskId($courseTask['id']);
        if ($taskStatus['status'] == 'end') {
            return $this->createJsonResponse(array('result' => false, 'message' => '该活动已结束，不能再提交！'));
        }

        // $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($courseTask['id']);
        // $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        // if ($lesson['status'] != 'teaching') {
        //     return $this->createJsonResponse(array('result' => false, 'message' => '该活动已结束，不能再提交！'));
        // }

        if ($request->getMethod() === 'POST') {
            $activity = $this->getActivityService()->getActivity($testpaperResult['lessonId']);
            $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);


            if ($activity['startTime'] && $activity['startTime'] > time()) {
                return $this->createJsonResponse(array('result' => false, 'message' => '考试未开始，不能提交！'));
            }

            if ($activity['endTime'] && time() > $activity['endTime']) {
                return $this->createJsonResponse(array('result' => false, 'message' => '考试时间已过，不能再提交！'));
            }

            $formData = $request->request->all();

            $paperResult = $this->getTestpaperService()->finishTest($testpaperResult['id'], $formData);

            if ($testpaperActivity['finishCondition']['type'] === 'submit') {
                $response = array('result' => true, 'message' => '');
            } elseif ($testpaperActivity['finishCondition']['type'] === 'score'
                && $paperResult['status'] === 'finished'
                && $paperResult['score'] > $testpaperActivity['finishCondition']['finishScore']) {
                $response = array('result' => true, 'message' => '');
            } else {
                $response = array('result' => false, 'message' => '');
            }

            return $this->createJsonResponse($response);
        }
    }

    public function showResultAction(Request $request, $resultId)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperResult['testId'], $testpaperResult['type']);

        if (!$testpaper) {
            return $this->createMessageResponse('info', '该试卷已删除，不能查看结果');
        }

        if ($testpaperResult['status'] === 'doing') {
            return $this->redirect($this->generateUrl('testpaper_show', array('resultId' => $testpaperResult['id'])));
        }

        $canLookTestpaper = $this->getTestpaperService()->canLookTestpaper($testpaperResult['id']);
        if (!$canLookTestpaper) {
            return $this->createMessageResponse('info', 'access denied');
        }

        $builder = $this->getTestpaperService()->getTestpaperBuilder($testpaper['type']);
        $questions = $builder->showTestItems($testpaper['id'], $testpaperResult['id']);

        $accuracy = $this->getTestpaperService()->makeAccuracy($testpaperResult['id']);

        $total = $this->makeTestpaperTotal($testpaper, $questions);

        $favorites = $this->getQuestionService()->findUserFavoriteQuestions($testpaperResult['userId']);

        $student = $this->getUserService()->getUser($testpaperResult['userId']);

        $attachments = $this->getTestpaperService()->findAttachments($testpaper['id']);

        $activity = $this->getActivityService()->getActivity($testpaperResult['lessonId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $task = $this->getTaskService()->getTaskByCourseIdAndActivityId($activity['fromCourseId'], $activity['id']);

        $course = $this->getCourseService()->getCourse($activity['fromCourseId']);

        return $this->render('testpaper/result.html.twig', array(
            'questions' => $questions,
            'accuracy' => $accuracy,
            'paper' => $testpaper,
            'paperResult' => $testpaperResult,
            'favorites' => ArrayToolkit::column($favorites, 'questionId'),
            'total' => $total,
            'student' => $student,
            'source' => $request->query->get('source', 'course'),
            'attachments' => $attachments,
            'questionTypes' => $this->getCheckedQuestionType($testpaper),
            'task' => $task,
            'course' => $course,
            'action' => $request->query->get('action', ''),
            'target' => $testpaperActivity,
        ));
    }

    public function statisAction(Request $request, $activityId, $testId)
    {
        $testpaper = $this->getTestpaperService()->getTestpaper($testId);

        if (!$testpaper) {
            return $this->createMessageResponse('info', '该试卷已删除，不能查看结果');
        }

        $attachments = $this->getTestpaperService()->findAttachments($testpaper['id']);

        $activity = $this->getActivityService()->getActivity($activityId);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $task = $this->getTaskService()->getTaskByCourseIdAndActivityId($activity['fromCourseId'], $activity['id']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        list(
            $questionTypes,
            $questions,
            $total,
            $statis
        ) = $this->fetchQuestions($activityId);

        $member = $this->getCourseMemberService()->countMembers(array('courseId' => $activity['fromCourseId'], 'role' => 'student'));
        $results = $this->getTestpaperService()->findItemResultsByTestIdAndLessonId($testpaper['id'], $activity['id']);
        $course = $this->getCourseService()->getCourse($activity['fromCourseId']);

        return $this->render('activity/testpaper/statis.html.twig', array(
            'paper' => $testpaper,
            'source' => $request->query->get('source', 'course'),
            'attachments' => $attachments,
            'task' => $task,
            'action' => $request->query->get('action', ''),
            'target' => $testpaperActivity,
            'activity' => $activity,
            'questionTypes' => $questionTypes,
            'questions' => $questions,
            'total' => $total,
            'statis' => $statis,
            'course' => $course,
            'memberNum' => $member,
            'actualNum' => count($results),
            'lesson' => $lesson,
        ));
    }

    public function fetchQuestionsAction(Request $request, $activityId, $testId)
    {
        list(
            $questionTypes,
            $questions,
            $total,
            $statis
        ) = $this->fetchQuestions($activityId);
        $activity = $this->getActivityService()->getActivity($activityId);
        $results = $this->getTestpaperService()->findItemResultsByTestIdAndLessonId($testId, $activityId);

        return $this->render('activity/testpaper/statis-item.html.twig', array(
            'questionTypes' => $questionTypes,
            'questions' => $questions,
            'total' => $total,
            'statis' => $statis,
            'actualNum' => count($results),
        ));
    }

    protected function fetchQuestions($activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        $testpaper = $this->getTestpaperService()->getTestpaper($testpaperActivity['mediaId']);
        $builder = $this->getTestpaperService()->getTestpaperBuilder($testpaper['type']);

        $questions = $builder->showTestItems($testpaper['id']);
        $total = $this->makeTestpaperTotal($testpaper, $questions);
        $results = $this->getTestpaperService()->findItemResultsByTestIdAndLessonId($testpaper['id'], $activity['id']);
        $resultIds = ArrayToolkit::column($results, 'id');
        $questionResults = $this->getTestpaperService()->findItemResultsByResultIds($resultIds);
        $questionResults = ArrayToolkit::group($questionResults, 'questionId');

        $response = array();
        foreach ($questionResults as $questionId => $results) {
            foreach ($results as $key => $result) {
                if (!is_array($result['answer'])) {
                    continue;
                }
                foreach ($result['answer'] as $answer) {
                    if (!empty($response[$questionId][$answer])) {
                        $response[$questionId][$answer] ++;
                    } else {
                        $response[$questionId][$answer] = 1;
                    }
                }
            }
        }

        $statis = $this->buildStatis($questions, $response, $questionResults);

        $questionTypes = $this->getCheckedQuestionType($testpaper);

        return array($questionTypes, $questions, $total, $statis);
    }

    private function buildStatis($allQuestions, $response, $questionResults)
    {
        $statis = array();
        foreach ($allQuestions as $questions) {
            $statis = $this->buildQuestionStatis($questions, $response, $questionResults, $statis);
        }

        return $statis;
    }

    private function buildQuestionStatis($questions, $response, $questionResults, $statis)
    {
        foreach ($questions as $questionId => $question) {
            if (empty($question['type']) || $question['type'] == 'material') {
                $statis = $this->buildQuestionStatis($question['subs'], $response, $questionResults, $statis);

                continue;
            }

            if ($question['type'] == 'determine') {
                $question['metas']['choices'] = array(
                    0, 1
                );
            }

            if (in_array($question['type'], array('single_choice', 'choice', 'uncertain_choice', 'determine'))) {
                if (!empty($questionResults[$question['id']])) {
                    $statis[$question['id']]['realNum'] = count($questionResults[$question['id']]);
                } else {
                    $statis[$question['id']]['realNum'] = 0;
                }
                if (!empty($question['metas']['choices'])) {
                    foreach ($question['metas']['choices'] as $key => $choice) {
                        if (!empty($response[$question['id']][$key]) && !empty($statis[$question['id']]['realNum'])) {
                            $statis[$question['id']][$key]['num'] = $response[$question['id']][$key];
                            $statis[$question['id']][$key]['percent'] = $response[$question['id']][$key] / $statis[$question['id']]['realNum'] * 100;
                        } else {
                            $statis[$question['id']][$key]['num'] = 0;
                            $statis[$question['id']][$key]['percent'] = 0;
                        }
                    }
                }
            }
        }

        return $statis;
    }

    protected function makeTestpaperTotal($testpaper, $items)
    {
        $total = array();
        if (empty($testpaper['metas']['counts'])) {
            return $total;
        }
        foreach ($testpaper['metas']['counts'] as $type => $count) {
            if (empty($items[$type])) {
                $total[$type]['score'] = 0;
                $total[$type]['number'] = 0;
                $total[$type]['missScore'] = 0;
            } else {
                $total[$type]['score'] = array_sum(ArrayToolkit::column($items[$type], 'score'));
                $total[$type]['number'] = count($items[$type]);

                if (array_key_exists('missScore', $testpaper['metas'])
                    && array_key_exists($type, $testpaper['metas']['missScore'])) {
                    $total[$type]['missScore'] = $testpaper['metas']['missScore'][$type];
                } else {
                    $total[$type]['missScore'] = 0;
                }
            }
        }

        return $total;
    }

    public function doTestpaperAction(Request $request, $testId, $lessonId, $course, $task)
    {
        $user = $this->getUser();

        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testId, 'testpaper');

        $fields = $this->getTestpaperFields($lessonId);
        $testpaperResult = $this->getTestpaperService()->startTestpaper($testpaper['id'], $fields);


        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperResult['testId'], $testpaperResult['type']);

        $questions = $this->getTestpaperService()->showTestpaperItems($testpaper['id'], $testpaperResult['id']);

        $total = $this->getTestpaperService()->countQuestionTypes($testpaper, $questions);

        $favorites = $this->getQuestionService()->findUserFavoriteQuestions($testpaperResult['userId']);

        $activity = $this->getActivityService()->getActivity($testpaperResult['lessonId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        if ($testpaperActivity['testMode'] === 'realTime') {
            $testpaperResult['usedTime'] = time() - $activity['startTime'];
        }

        $attachments = $this->getTestpaperService()->findAttachments($testpaper['id']);
        $limitedTime = $testpaperActivity['limitedTime'] * 60 - $testpaperResult['usedTime'];
        $limitedTime = $limitedTime > 0 ? $limitedTime : 1;

        return $this->render('activity/testpaper/student-end-lesson-show.html.twig', array(
            'questions' => $questions,
            'limitedTime' => $limitedTime,
            'paper' => $testpaper,
            'paperResult' => $testpaperResult,
            'activity' => $activity,
            'testpaperActivity' => $testpaperActivity,
            'favorites' => ArrayToolkit::column($favorites, 'questionId'),
            'total' => $total,
            'attachments' => $attachments,
            'questionTypes' => $this->getCheckedQuestionType($testpaper),
            'showTypeBar' => 1,
            'showHeader' => 0,
            'isDone' => true,
            'course' => $course,
            'task' => $task,
        ));
    }

    protected function getTestpaperFields($activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        if (!$activity || !$testpaperActivity) {
            return array();
        }

        return array(
            'lessonId' => $activityId,
            'courseId' => $activity['fromCourseId'],
            'limitedTime' => $testpaperActivity['limitedTime'],
        );
    }

    protected function getCheckedQuestionType($testpaper)
    {
        $questionTypes = array();
        if (!empty($testpaper['metas']['counts'])) {
            foreach ($testpaper['metas']['counts'] as $type => $count) {
                if ($count > 0) {
                    $questionTypes[] = $type;
                }
            }
        }

        return $questionTypes;
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->createService('CustomBundle:Testpaper:TestpaperService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return TestpaperActivityService
     */
    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getQuestionService()
    {
        return $this->createService('Question:QuestionService');
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

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }
}
