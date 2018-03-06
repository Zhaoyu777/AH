<?php
namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Biz\User\Service\TokenService;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class TestpaperController extends BaseController
{
    public function resultAction($taskId)
    {
        $user = $this->getUser();
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperActivity['mediaId'], $activity['mediaType']);
        $testpaperResult = $this->getTestpaperService()->getUserLatelyResultByTestId($user['id'], $testpaperActivity['mediaId'], $activity['fromCourseId'], $activity['id'], $activity['mediaType']);
        if (!empty($testpaperResult)) {
            $testpaperResult = ArrayToolkit::parts($testpaperResult, array(
                'id',
                'paperName',
                'testId',
                'score',
                'passedStatus',
                'beginTime',
                'status',
            ));
            $questions = $this->getTestpaperService()->showTestpaperItems($testpaper['id'], $testpaperResult['id']);
        } else {
            $questions = $this->getTestpaperService()->showTestpaperItems($testpaper['id']);
        }

        $questions = $this->questionsRevise($questions);

        $accuracy = $this->getTestpaperService()->makeAccuracy($testpaperResult['id']);

        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        return $this->createJsonResponse(array(
            'result' => $testpaperResult,
            'accuracy' => $accuracy,
            'status' => $status['status'],
            'questions' => $questions
        ));
    }

    public function statisAction($taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperActivity['mediaId'], $activity['mediaType']);

        $allQuestions = $this->getTestpaperService()->showTestpaperItems($testpaper['id']);
        $Results = $this->getTestpaperService()->findItemResultsByTestIdAndLessonId($testpaper['id'], $activity['id']);
        $resultIds = ArrayToolkit::column($Results, 'id');
        $questionResults = $this->getTestpaperService()->findItemResultsByResultIds($resultIds);
        $questionResults = ArrayToolkit::group($questionResults, 'questionId');
        $response = array();
        foreach ($questionResults as $questionId => $results) {
            foreach ($results as $key => $result) {
                foreach ($result['answer'] as $answer) {
                    if (!empty($response[$questionId][$answer])) {
                        $response[$questionId][$answer] ++ ;
                    } else {
                        $response[$questionId][$answer] = 1;
                    }
                }
            }
        }

        $statis = $this->buildStatis($allQuestions, $response, $questionResults);

        return $this->createJsonResponse(array(
            'statis' => $statis,
        ));
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
            if ($question['type'] == 'material') {
                $statis = $this->buildQuestionStatis($question['subs'], $response, $statis, $statis);

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

    public function doTestpaperAction(Request $request, $taskId)
    {
        $user = $this->getUser();
        $task = $this->getTaskService()->getTask($taskId);
        $lessonId = $task['activityId'];

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testId = $testpaperActivity['mediaId'];

        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testId, 'testpaper');

        if (empty($testpaper)) {
            throw $this->createResourceNotFoundException('testpaper', $testId, '该试卷不存在。');
        }

        //下面的代码需要扔到service作异常处理
        // if ($testpaper['status'] === 'draft') {
        //     return $this->createMessageResponse('info', $this->getServiceKernel()->trans('该试卷未发布，如有疑问请联系老师！'));
        // }

        // if ($testpaper['status'] === 'closed') {
        //     return $this->createMessageResponse('info', $this->getServiceKernel()->trans('该试卷已关闭，如有疑问请联系老师！'));
        // }

        // $result = $this->testpaperActivityCheck($lessonId, $testpaper);
        // if (!$result['result']) {
        //     return $this->createMessageResponse('info', $result['message']);
        // }

        $fields = $this->getTestpaperFields($lessonId);
        $testpaperResult = $this->getTestpaperService()->startTestpaper($testpaper['id'], $fields);
        if (!empty($testpaperResult)) {
            $testpaperResult = ArrayToolkit::parts($testpaperResult, array(
                'id',
                'paperName',
                'testId',
                'score',
                'passedStatus',
                'beginTime',
                'status',
            ));
        }
        $questions = $this->getTestpaperService()->showTestpaperItems($testpaper['id'], $testpaperResult['id']);
        $questions = $this->questionsRevise($questions);
        $accuracy = $this->getTestpaperService()->makeAccuracy($testpaperResult['id']);

        return $this->createJsonResponse(array(
            'result' => $testpaperResult,
            'accuracy' => $accuracy,
            'questions' => $questions
        ));
    }

    public function finishTestAction(Request $request, $resultId)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

        if (!empty($testpaperResult) && !in_array($testpaperResult['status'], array('doing', 'paused'))) {
            return $this->createJsonResponse(array('result' => false, 'message' => '试卷已提交，不能再修改答案！'));
        }

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

            $accuracy = $this->getTestpaperService()->makeAccuracy($testpaperResult['id']);
            $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);
            if (!empty($testpaperResult)) {
                $testpaperResult = ArrayToolkit::parts($testpaperResult, array(
                    'id',
                    'paperName',
                    'testId',
                    'score',
                    'passedStatus',
                    'beginTime',
                    'status',
                ));
                $questions = $this->getTestpaperService()->showTestpaperItems($testpaperResult['testId'], $testpaperResult['id']);
            } else {
                $questions = $this->getTestpaperService()->showTestpaperItems($testpaperResult['testId']);
            }

            $questions = $this->questionsRevise($questions);

            return $this->createJsonResponse(array(
                'result' => $testpaperResult,
                'questions' => $questions,
                'accuracy' => $accuracy,
            ));
        }
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

    protected function testpaperActivityCheck($activityId, $testpaper)
    {
        $user = $this->getUser();

        $activity = $this->getActivityService()->getActivity($activityId);

        $canTakeCourse = $this->getCourseService()->canTakeCourse($activity['fromCourseId']);
        if (!$canTakeCourse) {
            return array('result' => false, 'message' => $this->getServiceKernel()->trans('access denied!'));
        }

        $result = array('result' => true, 'message' => '');
        if (!$activity) {
            return $result;
        }

        if ($activity['startTime'] && $activity['startTime'] > time()) {
            return array(
                'result' => false,
                'message' => $this->getServiceKernel()->trans('考试未开始，请在'.date('Y-m-d H:i:s', $activity['startTime']).'之后再来！'),
            );
        }

        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testpaperResult = $this->getTestpaperService()->getUserLatelyResultByTestId(
            $user['id'],
            $testpaper['id'],
            $activity['fromCourseSetId'],
            $activityId,
            $testpaper['type']
        );

        if ($testpaperActivity['doTimes'] && $testpaperResult && $testpaperResult['status'] === 'finished') {
            return array('result' => false, 'message' => $this->getServiceKernel()->trans('该试卷只能考一次，不能再考！'));
        }

        if ($testpaperActivity['redoInterval']) {
            $nextDoTime = $testpaperResult['checkedTime'] + $testpaperActivity['redoInterval'] * 3600;
            if ($nextDoTime > time()) {
                return array(
                    'result' => false,
                    'message' => $this->getServiceKernel()->trans('教师设置了重考间隔，请在'.date('Y-m-d H:i:s', $nextDoTime).'之后再考！'),
                );
            }
        }

        return $result;
    }

    private function questionsRevise($questions)
    {
        foreach ($questions as &$question) {
            $question = array_values($question);
        }
        if (!empty($questions['fill'])) {
            foreach ($questions['fill'] as $key => &$question) {
                $question['stem'] = preg_split('/\[\[.+?\]\]/', $question['stem']);
            }
        }
        if (!empty($questions['material'])) {
            foreach ($questions['material'] as $key => &$material) {
                foreach ($material['subs'] as $key => &$question) {
                    if ($question['type'] == 'fill') {
                        $question['stem'] = preg_split('/\[\[.+?\]\]/', $question['stem']);
                    }
                }
            }
        }

        return $questions;
    }

    private function getTaskStatus($taskId)
    {
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        return $status['status'];
    }

    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getTestpaperService()
    {
        return $this->createService('CustomBundle:Testpaper:TestpaperService');
    }

    protected function getQuestionService()
    {
        return $this->createService('Question:QuestionService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }
}
