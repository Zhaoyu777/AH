<?php

namespace CustomBundle\Biz\RandomTestpaper\Builder;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Context\Biz;

class RandomTestpaperBuilder
{
    protected $biz;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function buildItems($activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $config = $this->getActivityService()->getActivityConfig('randomTestpaper');
        $randomTestpaper = $config->get($activity['mediaId']);

        $seq = 1;
        $testpaperPattern = $this->getTestpaperService()->getTestpaperPattern('questionType');
        $questions = $this->getQuestions($randomTestpaper['metas']);

        shuffle($questions);
        $typedQuestions = ArrayToolkit::group($questions, 'type');

        $items = array();
        foreach ($randomTestpaper['metas']['counts'] as $type => $count) {
            if ($count > 0 && !empty($typedQuestions)) {
                for ($i=0; $i < $count; $i++) {
                    $typedQuestions[$type][$i]['questionId'] = $typedQuestions[$type][$i]['id'];
                    $typedQuestions[$type][$i]['seq'] = $seq;
                    $typedQuestions[$type][$i]['score'] = $randomTestpaper['metas']['scores'][$type];
                    $items[$type][] = $typedQuestions[$type][$i];
                    $seq ++;
                }
            }
        }

        return $items;
    }

    public function makeAccuracy($questions)
    {
        $accuracy = array();

        foreach ($questions as $type => $typeQuestions) {
            $rightCount = 0;
            $sumScore = 0;
            $totalScore = 0;
            foreach ($typeQuestions as $key => $question) {
                if ($question['status'] == 'right') {
                    $rightCount ++;
                }
                $sumScore += $question['realScore'];
                $totalScore += $question['score'];
            }

            $accuracy[$type] = array(
                'all' => count($typeQuestions),
                'right' => $rightCount,
                'score' => $sumScore,
                'totalScore' => $totalScore,
            );
        }

        return $accuracy;
    }

    public function createTestpaper($taskId, $questionIds, $answers)
    {
        $user = $this->biz['user'];
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('randomTestpaper');
        $randomTestpaper = $config->get($activity['mediaId']);

        $questions = $this->getQuestionService()->findQuestionsByIds($questionIds);
        $judgeAnswers = $this->judgeTestpaper($randomTestpaper, $questions, $answers);

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $testpaper = array(
            'courseId' => $task['courseId'],
            'lessonId' => $lessonTask['lessonId'],
            'taskId' => $task['id'],
            'score' => $judgeAnswers['sumScore'],
            'activityId' => $task['activityId'],
            'userId' => $user['id'],
            'passedScore' => $randomTestpaper['passedScore'],
            'status' => $judgeAnswers['sumScore'] >= $randomTestpaper['passedScore'] ? 'passed':'unpassed',
        );
        $testpaper = $this->getRandomTestpaperService()->createTestpaper($testpaper);

        $seq = 1;
        foreach ($questionIds as $questionId) {
            if (!empty($answers[$questionId])) {
                $answer = $answers[$questionId];
            } else {
                $answer = null;
            }

            if (empty($judgeAnswers[$questionId])) {
                $judgeAnswers[$questionId] = null;
            }

            $item = array(
                'testId' => $testpaper['id'],
                'seq' => $seq,
                'questionId' => $questionId,
                'answer' => $answer,
                'score' => $randomTestpaper['metas']['scores'][$questions[$questionId]['type']],
                'realScore' => empty($judgeAnswers[$questionId]['score']) ? 0:$judgeAnswers[$questionId]['score'],
                'missScore' => empty($judgeAnswers[$questionId]['missScore']) ? 0:$judgeAnswers[$questionId]['missScore'],
                'status' => $judgeAnswers[$questionId]['status'],
                'questionType' => $questions[$questionId]['type'],
            );
            $seq ++;
            $this->getRandomTestpaperService()->createTestpaperItem($item);
        }

        return $testpaper;
    }

    public function judgeTestpaper($randomTestpaper, $questions, $answers)
    {
        $judgeAnswers = array(
            'sumScore' => 0,
        );
        $missScores = $randomTestpaper['metas']['missScores'];
        $scores = $randomTestpaper['metas']['scores'];

        foreach ($answers as $questionId => $answer) {
            $question = $questions[$questionId];
            $question['missScore'] = empty($missScores[$question['type']]) ? 0 : $missScores[$question['type']];
            $question['score'] = empty($scores[$question['type']]) ? 0 : $scores[$question['type']];
            $answerStatus = $this->getQuestionService()->judgeQuestion($question, $answer);
            $judgeAnswers[$questionId] = array(
                'status' => $answerStatus['status'],
                'score' => $answerStatus['score'],
                'missScore' => $question['missScore'],
            );
            $judgeAnswers['sumScore'] += $answerStatus['score'];
        }

        return $judgeAnswers;
    }

    public function showTestItems($testId)
    {
        $testpaper = $this->getRandomTestpaperService()->getTestpaper($testId);
        $activity = $this->getActivityService()->getActivity($testpaper['activityId']);
        $config = $this->getActivityService()->getActivityConfig('randomTestpaper');
        $randomTestpaper = $config->get($activity['mediaId']);

        $items = $this->getRandomTestpaperService()->findTestpaperItemsByTestId($testId);
        $items = ArrayToolkit::index($items, 'questionId');
        $questionIds = ArrayToolkit::column($items, 'questionId');
        $questions = $this->getQuestionService()->findQuestionsByIds($questionIds);

        $formatItems = array();
        foreach ($items as $questionId => $item) {
            $question = empty($questions[$questionId]) ? array() : $questions[$questionId];

            if (empty($question)) {
                $question = array(
                    'id' => $item['questionId'],
                    'isDeleted' => true,
                    'stem' => '此题已删除',
                    'score' => 0,
                    'answer' => '',
                    'type' => $item['questionType'],
                );
            }

            if (!empty($randomTestpaper['metas']['scores'][$item['questionType']])) {
                $question['score'] = $randomTestpaper['metas']['scores'][$item['questionType']];
            } else {
                $question['score'] = $question['score'];
            }
            $question['seq'] = $item['seq'];
            $question['missScore'] = $item['missScore'];
            $question['status'] = $item['status'];
            $question['realScore'] = $item['realScore'];

            if (!empty($items[$questionId])) {
                $question['testResult'] = $items[$questionId];
            }

            $formatItems[$item['questionType']][$questionId] = $question;
        }

        foreach ($formatItems as &$question) {
            $question = array_values($question);
        }

        return $formatItems;
    }

    protected function getQuestions($options)
    {
        $conditions = array(
            'parentId' => 0,
            'courseSetId' => $options['range']['courseSetId'],
        );

        if (!empty($options['range']['start'])) {
            $conditions['lessonIdGT'] = $options['range']['start'];
        }

        if (!empty($options['range']['end'])) {
            $conditions['lessonIdLT'] = $options['range']['end'];
        }

        if (!empty($options['range']['courseId'])) {
            $conditions['courseId'] = $options['range']['courseId'];
        }

        if (!empty($options['range']['lessonId'])) {
            $conditions['lessonId'] = $options['range']['lessonId'];
        }

        $total = $this->getQuestionService()->searchCount($conditions);

        return $this->getQuestionService()->search(
            $conditions,
            array('createdTime' => 'DESC'),
            0,
            $total
        );
    }

    protected function getQuestionService()
    {
        return $this->biz->service('Question:QuestionService');
    }

    protected function getCourseLessonService()
    {
        return $this->biz->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getTaskService()
    {
        return $this->biz->service('CustomBundle:Task:TaskService');
    }

    protected function getRandomTestpaperService()
    {
        return $this->biz->service('CustomBundle:RandomTestpaper:RandomTestpaperService');
    }

    protected function getTestpaperService()
    {
        return $this->biz->service('Testpaper:TestpaperService');
    }

    protected function getActivityService()
    {
        return $this->biz->service('Activity:ActivityService');
    }
}
