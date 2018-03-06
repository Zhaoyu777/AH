<?php

namespace Tests\Unit\CustomBundle\Questionnaire;

use Biz\BaseTestCase;

class QuestionServiceTest extends BaseTestCase
{
    public function testCreate()
    {
        $question = array(
            'type' => 'single_choice',
            'stem' => 'test single choice question 1.',
            'questionnaireId' => 1,
            'metas' => array(
                'question 1 -> choice 1',
                'question 1 -> choice 2',
                'question 1 -> choice 3',
                'question 1 -> choice 4',
            ),
        );

        $question1 = $this->getQuestionService()->createQuestion($question);

        $this->assertEquals($question['type'], $question1['type']);
        $this->assertEquals($question['stem'], $question1['stem']);
        $this->assertEquals($question['questionnaireId'], $question1['questionnaireId']);
    }

    public function testCreateQuestionResult()
    {
        $questionResults = $this->createQuestionResult();

        $this->assertEquals(1, $questionResults['questionnaireResultId']);
        $this->assertEquals(1, $questionResults['questionId']);
    }

    public function testGet()
    {
        $question = array(
            'type' => 'single_choice',
            'stem' => 'test single choice question 1.',
            'questionnaireId' => 1,
            'metas' => array(
                'question 1 -> choice 1',
                'question 1 -> choice 2',
                'question 1 -> choice 3',
                'question 1 -> choice 4',
            ),
        );

        $question1 = $this->getQuestionService()->createQuestion($question);
        $question2 = $this->getQuestionService()->getQuestion($question1['id']);
        $this->assertEquals($question1['type'], $question2['type']);
        $this->assertEquals($question1['stem'], $question2['stem']);
        $this->assertEquals($question1['questionnaireId'], $question2['questionnaireId']);
    }

    public function testUpdate()
    {
        $question = $this->createQuestion();
        $fields = array(
            'stem' => 'test single choice question 2.',
            'metas' => array(
                'question 1 -> choice 5',
                'question 1 -> choice 6',
                'question 1 -> choice 7',
                'question 1 -> choice 8',
            ),
        );
        $question1 = $this->getQuestionService()->updateQuestion($question['id'], $fields);

        $this->assertEquals($fields['stem'], $question1['stem']);
        $this->assertArrayEquals($fields['metas'], $question1['metas']);
    }

    public function testDelete()
    {
        $question = $this->createQuestion();
        $question1 = $this->createQuestion1();

        $this->getQuestionService()->deleteQuestion($question['id']);

        $question = $this->getQuestionService()->getQuestion($question['id']);

        $this->assertNull($question);
    }

    public function testDeletes()
    {
        $question = $this->createQuestion();
        $question1 = $this->createQuestion1();
        $this->getQuestionService()->deleteQuestions($question['questionnaireId'], array($question['id'], $question1['id']));

        $question = $this->getQuestionService()->getQuestion($question['id']);
        $question1 = $this->getQuestionService()->getQuestion($question1['id']);

        $this->assertNull($question);
        $this->assertNull($question1);
    }

    public function testFindQuestionsByQuestionnaireId()
    {
        $question = $this->createQuestion();
        $question1 = $this->createQuestion1();
        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($question1['questionnaireId'], 0, 2);
        $this->assertEquals(2, count($questions));
    }

    public function testDeleteQuestionsByQuestionnaireId()
    {
        $question = $this->createQuestion();
        $question1 = $this->createQuestion1();
        $this->getQuestionService()->deleteQuestionsByQuestionnaireId($question['questionnaireId']);
        $question = $this->getQuestionService()->findQuestionsByQuestionnaireId($question1['questionnaireId'], 0, 2);
        $this->assertTrue(empty($question));
    }

    public function testDeleteQuestionResultsByQuestionnaireResultIds()
    {
        $questionResult = $this->createQuestionResult();
        $questionResult1 = $this->createQuestionResult1();
        $result = $this->getQuestionService()->deleteQuestionResultsByQuestionnaireResultIds(array($questionResult1['questionnaireResultId'], $questionResult['questionnaireResultId']));
        $this->assertEquals(2, $result);
    }

    public function testDeleteQuestionResultsByQuestionId()
    {
        $questionResult = $this->createQuestionResult();
        $result = $this->getQuestionService()->deleteQuestionResultsByQuestionId($questionResult['questionId']);
        $this->assertEquals(1, $result);
    }

    public function testFindQuestionResultsByQuestionnaireResultIds()
    {
        $questionResult = $this->createQuestionResult();
        $questionResult1 = $this->createQuestionResult1();
        $questionResults = $this->getQuestionService()->findQuestionResultsByQuestionnaireResultIds(array($questionResult['questionnaireResultId'], $questionResult1['questionnaireResultId']));
        $this->assertEquals(2, count($questionResults));
    }

    public function testFindQuestionsCountByQuestionnaireId()
    {
        $question = $this->createQuestion();
        $question1 = $this->createQuestion1();

        $count = $this->getQuestionService()->findQuestionsCountByQuestionnaireId($question['questionnaireId']);
        $this->assertEquals(2, $count);
    }

    public function testSearch()
    {
        $question1 = $this->createQuestion();
        $question2 = $this->createQuestion1();

        $conditions = array(
            'courseSetId' => 1,
        );

        $questions = $this->getQuestionService()->searchQuestions($conditions, array('createdTime' => 'DESC'), 0, PHP_INT_MAX);
        $this->assertEquals(2, count($questions));
    }

    public function testFindQuestionsByIds()
    {
        $question = $this->createQuestion();
        $question1 = $this->createQuestion1();
        $question2 = $this->createQuestion2();
        $questions = $this->getQuestionService()->findQuestionsByIds(array($question['id'], $question1['id'], $question2['id']));

        $this->assertEquals(3, count($questions));
    }

    public function testSearchCount()
    {
        $question1 = $this->createQuestion();
        $question2 = $this->createQuestion1();

        $conditions = array(
            'courseSetId' => 1,
            'questionnaireId' => 1,
        );

        $count = $this->getQuestionService()->searchQuestionsCount($conditions);
        $this->assertEquals(2, $count);
    }

    protected function createQuestion()
    {
        $question = array(
            'type' => 'single_choice',
            'stem' => 'test single choice question 1.',
            'courseSetId' => 1,
            'questionnaireId' => 1,
            'metas' => array(
                'question 1 -> choice 1',
                'question 1 -> choice 2',
                'question 1 -> choice 3',
                'question 1 -> choice 4',
            ),
        );

        return $this->getQuestionService()->createQuestion($question);
    }

    protected function createQuestion1()
    {
        $question = array(
            'type' => 'single_choice',
            'stem' => 'test single choice question 2.',
            'courseSetId' => 1,
            'questionnaireId' => 1,
            'metas' => array(
                'question 2 -> choice 1',
                'question 2 -> choice 2',
                'question 2 -> choice 3',
                'question 2 -> choice 4',
            ),
        );

        return $this->getQuestionService()->createQuestion($question);
    }

    protected function createQuestionResult()
    {
        $fields = array(
            'questionnaireResultId' => 1,
            'questionId' => 1,
            'answer' => array(
                'question 2 -> choice 1',
                'question 2 -> choice 2',
                'question 2 -> choice 3',
                'question 2 -> choice 4',
            ),
        );

        return $this->getQuestionService()->createQuestionResult($fields);
    }

    protected function createQuestionResult1()
    {
        $fields = array(
            'questionnaireResultId' => 2,
            'questionId' => 2,
            'answer' => array(
                'question 2 -> choice 1',
                'question 2 -> choice 2',
                'question 2 -> choice 3',
                'question 2 -> choice 4',
            ),
        );

        return $this->getQuestionService()->createQuestionResult($fields);
    }

    protected function createQuestion2()
    {
        $question = array(
            'type' => 'single_choice',
            'stem' => 'test single choice question 3.',
            'courseSetId' => 1,
            'questionnaireId' => 2,
            'metas' => array(
                'question 2 -> choice 1',
                'question 2 -> choice 2',
                'question 2 -> choice 3',
                'question 2 -> choice 4',
            ),
        );

        return $this->getQuestionService()->createQuestion($question);
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionService');
    }
}
