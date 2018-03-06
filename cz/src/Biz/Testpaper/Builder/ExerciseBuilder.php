<?php

namespace Biz\Testpaper\Builder;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Context\Biz;

class ExerciseBuilder implements TestpaperBuilderInterface
{
    protected $biz;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function build($fields)
    {
        $fields['type'] = 'exercise';
        $fields['status'] = 'open';
        $fields['pattern'] = 'questionType';
        $fields['passedCondition'] = empty($fields['passedCondition']) ? array(0) : $fields['passedCondition'];

        $fields = $this->filterFields($fields);

        return $this->getTestpaperService()->createTestpaper($fields);
    }

    public function canBuild($options)
    {
        $questions = $this->getQuestions($options);
        $questionCount = count($questions);

        if ($questionCount < $options['itemCount']) {
            $lessNum = $options['itemCount'] - $questionCount;

            return array('status' => 'no', 'lessNum' => $lessNum);
        } else {
            return array('status' => 'yes');
        }
    }

    public function showTestItems($testId, $resultId = 0)
    {
        $exercise = $this->getTestpaperService()->getTestpaperByIdAndType($testId, 'exercise');

        $itemResults = array();
        if ($resultId) {
            $exerciseResult = $this->getTestpaperService()->getTestpaperResult($resultId);

            $itemResults = $this->getTestpaperService()->findItemResultsByResultId($exerciseResult['id'], true);
            $itemResults = ArrayToolkit::index($itemResults, 'questionId');
        }

        if ($itemResults) {
            $questionIds = ArrayToolkit::column($itemResults, 'questionId');
            $questions = $this->getQuestionService()->findQuestionsByIds($questionIds);

            $questionIds = array();
            foreach ($questions as $question) {
                $questionIds[] = $question['parentId'] > 0 ? $question['parentId'] : $question['id'];
            }

            $questions = $this->getQuestionService()->findQuestionsByIds($questionIds);
        } else {
            $conditions = array(
                'types' => $exercise['metas']['questionTypes'],
                'courseSetId' => $exercise['courseSetId'],
                'parentId' => 0,
            );
            if (!empty($exercise['metas']['difficulty'])) {
                $conditions['difficulty'] = $exercise['metas']['difficulty'];
            }
            //兼容course1.0 start
            if (!empty($exercise['metas']['range']) && $exercise['metas']['range'] == 'lesson') {
                $filter = array(
                    'activityId' => $exercise['lessonId'],
                    'type' => 'exercise',
                    'courseId' => $exercise['courseId'],
                );
                $task = $this->getCourseTaskService()->searchTasks($filter, null, 0, 1);
                if ($task) {
                    $conditions = array(
                        'categoryId' => $task[0]['categoryId'],
                        'mode' => 'lesson',
                    );
                    $lessonTask = $this->getCourseTaskService()->searchTasks($conditions, null, 0, 1);
                    if ($lessonTask) {
                        $conditions['lessonId'] = $lessonTask[0]['id'];
                    }
                }
                unset($exercise['metas']['range']);
            }
            //兼容course1.0 end

            if (!empty($exercise['metas']['range']['courseId'])) {
                $conditions['courseId'] = $exercise['metas']['range']['courseId'];
            }

            if (!empty($exercise['metas']['range']['courseId']) && !empty($exercise['metas']['range']['lessonId'])) {
                $conditions['lessonId'] = $exercise['metas']['range']['lessonId'];
            }

            $count = $this->getQuestionService()->searchCount($conditions);
            $questions = $this->getQuestionService()->search(
                $conditions,
                array('createdTime' => 'DESC'),
                0,
                $count
            );
            shuffle($questions);

            $questions = array_slice($questions, 0, $exercise['itemCount']);
        }

        return $this->formatQuestions($questions, $itemResults);
    }

    public function filterFields($fields, $mode = 'create')
    {
        if (!empty($fields['questionTypes'])) {
            $fields['metas']['questionTypes'] = $fields['questionTypes'];
        }

        if (!empty($fields['difficulty'])) {
            $fields['metas']['difficulty'] = $fields['difficulty'];
        }

        if (!empty($fields['range'])) {
            $fields['metas']['range'] = $fields['range'];
        }

        if (!empty($fields['finishCondition'])) {
            $fields['passedCondition']['type'] = $fields['finishCondition'];
        }

        $fields = ArrayToolkit::parts($fields, array(
            'name',
            'itemCount',
            'courseId',
            'courseSetId',
            'lessonId',
            'type',
            'status',
            'pattern',
            'copyId',
            'metas',
            'passedCondition',
        ));

        return $fields;
    }

    public function updateSubmitedResult($resultId, $usedTime)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);
        $itemResults = $this->getTestpaperService()->findItemResultsByResultId($testpaperResult['id']);

        $fields = array(
            'status' => 'finished',
        );

        $accuracy = $this->getTestpaperService()->sumScore($itemResults);
        $fields['score'] = $accuracy['sumScore'];
        $fields['rightItemCount'] = $accuracy['rightItemCount'];

        $fields['usedTime'] = $usedTime + $testpaperResult['usedTime'];
        $fields['endTime'] = time();
        $fields['checkedTime'] = time();

        return $this->getTestpaperService()->updateTestpaperResult($testpaperResult['id'], $fields);
    }

    protected function formatQuestions($questions, $questionResults)
    {
        $formatQuestions = array();
        $index = 1;

        foreach ($questions as $question) {
            if (!empty($questionResults[$question['id']])) {
                $question['testResult'] = $questionResults[$question['id']];
            }

            $question['seq'] = $index;

            if ($question['subCount'] > 0) {
                $subQuestions = $this->getQuestionService()->findQuestionsByParentId($question['id']);
                array_walk($subQuestions, function (&$sub) use (&$index, $questionResults) {
                    $sub['seq'] = $index;
                    $sub['testResult'] = empty($questionResults[$sub['id']]) ? array() : $questionResults[$sub['id']];
                    ++$index;
                });
                $question['subs'] = $subQuestions;
            } else {
                ++$index;
            }

            $formatQuestions[$question['id']] = $question;
        }

        return $formatQuestions;
    }

    protected function getQuestions($options)
    {
        $conditions = array();

        if (!empty($options['range']) && !is_array($options['range'])) {
            $options['range'] = (array) json_decode($options['range']);
        }

        if (!empty($options['range']) && $options['range'] == 'lesson') {
            $conditions['lessonId'] = $options['range'];
        }

        if (!empty($options['range']['courseId'])) {
            $conditions['courseId'] = $options['range']['courseId'];
        }

        if (!empty($options['range']['lessonId'])) {
            $conditions['lessonId'] = $options['range']['lessonId'];
        }

        if (!empty($options['questionTypes'])) {
            $conditions['types'] = $options['questionTypes'];
        }

        if (!empty($options['types'])) {
            $conditions['types'] = explode(',', $options['types']);
        }

        if (!empty($options['difficulty'])) {
            $conditions['difficulty'] = $options['difficulty'];
        }

        $conditions['courseSetId'] = $options['courseSetId'];
        $conditions['parentId'] = 0;

        $total = $this->getQuestionService()->searchCount($conditions);

        return $this->getQuestionService()->search($conditions, array('createdTime' => 'DESC'), 0, $total);
    }

    protected function getQuestionService()
    {
        return $this->biz->service('Question:QuestionService');
    }

    protected function getTestpaperService()
    {
        return $this->biz->service('Testpaper:TestpaperService');
    }

    protected function getCourseTaskService()
    {
        return $this->biz->service('Task:TaskService');
    }
}
