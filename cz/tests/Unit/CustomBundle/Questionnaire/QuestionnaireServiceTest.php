<?php

namespace Tests\Unit\CustomBundle\Questionnaire;

use Biz\BaseTestCase;

class QuestionnaireServiceTest extends BaseTestCase
{
    public function testGet()
    {
        $questionnaire = $this->createQuestionnaire();
        $questionnaire1 = $this->getQuestionnaireService()->getQuestionnaire($questionnaire['id']);
        $this->assertEquals($questionnaire['title'], $questionnaire1['title']);
        $this->assertEquals($questionnaire['courseSetId'], $questionnaire1['courseSetId']);
        $this->assertEquals($questionnaire['updatedUserId'], $questionnaire1['updatedUserId']);
    }

    public function testCreate()
    {
        $questionnaire = array(
            'title' => 'bangbangyou',
            'courseSetId' => 1,
            'updatedUserId' => 1,
        );

        $questionnaire1 = $this->getQuestionnaireService()->createQuestionnaire($questionnaire);

        $this->assertEquals($questionnaire['title'], $questionnaire1['title']);
        $this->assertEquals($questionnaire['courseSetId'], $questionnaire1['courseSetId']);
        $this->assertEquals($questionnaire['updatedUserId'], $questionnaire1['updatedUserId']);
    }

    public function testUpdate()
    {
        $questionnaire = $this->createQuestionnaire();
        $fields = array(
            'title' => 'djb',
            'updatedUserId' => 2,
        );
        $questionnaire1 = $this->getQuestionnaireService()->updateQuestionnaire($questionnaire['id'], $fields);
        $this->assertEquals($fields['title'], $questionnaire1['title']);
        $this->assertEquals($fields['updatedUserId'], $questionnaire1['updatedUserId']);
    }

    public function testDelete()
    {
        $question = $this->createQuestionnaire();
        $this->getQuestionnaireService()->deleteQuestionnaire($question['id']);

        $question = $this->getQuestionnaireService()->getQuestionnaire($question['id']);

        $this->assertNull($question);
    }

    public function testDeletes()
    {
        $question = $this->createQuestionnaire();
        $question1 = $this->createQuestionnaire1();

        $this->getQuestionnaireService()->deleteQuestionnaires(array($question['id'], $question1['id']));

        $question = $this->getQuestionnaireService()->getQuestionnaire($question['id']);

        $this->assertNull($question);
    }

    public function testSearch()
    {
        $question1 = $this->createQuestionnaire();
        $question2 = $this->createQuestionnaire1();

        $conditions = array(
            'courseSetId' => 1,
        );

        $questions = $this->getQuestionnaireService()->searchQuestionnaires($conditions, array('createdTime' => 'DESC'), 0, PHP_INT_MAX);
        $this->assertEquals(2, count($questions));

        $questions = $this->getQuestionnaireService()->searchQuestionnaires(array('updatedUserId' => 1), array('createdTime' => 'DESC'), 0, PHP_INT_MAX);
        $this->assertEquals(1, count($questions));
    }

    public function testSearchCount()
    {
        $question1 = $this->createQuestionnaire();
        $question2 = $this->createQuestionnaire1();

        $conditions = array(
            'courseSetId' => 1,
        );

        $count = $this->getQuestionnaireService()->searchQuestionnaireCount($conditions);
        $this->assertEquals(2, $count);
    }

    public function testFindQuestionnaireByIds()
    {
        $question1 = $this->createQuestionnaire();
        $question2 = $this->createQuestionnaire1();

        $questionnaire = $this->getQuestionnaireService()->findQuestionnaireByIds(array($question1['id'], $question2['id']));
        $this->assertEquals(2, count($questionnaire));
    }

    public function testFindQuestionnairesByCourseSetId()
    {
        $questionnaire = $this->createQuestionnaire();
        $questionnaire1 = $this->getQuestionnaireService()->findQuestionnairesByCourseSetId($questionnaire['courseSetId']);
        $this->assertEquals($questionnaire['courseSetId'], $questionnaire1[0]['courseSetId']);
        $this->assertEquals($questionnaire['title'], $questionnaire1[0]['title']);
        $this->assertEquals($questionnaire['updatedUserId'], $questionnaire1[0]['updatedUserId']);
    }

    public function testStartQuestionnaire()
    {
        $questionnaire = $this->createQuestionnaire();
        $fields = array(
            'questionnaireId' => $questionnaire['id'],
            'userId' => 1,
            'status' => 'doing',
            'taskId' => 1,
            'activityId' => 1,
        );
        $questionnaireResult = $this->getQuestionnaireService()->startQuestionnaire($questionnaire['id'], $fields);
        $this->assertEquals($fields['questionnaireId'], $questionnaireResult['questionnaireId']);
        $this->assertEquals($fields['userId'], $questionnaireResult['userId']);
        $this->assertEquals($fields['status'], $questionnaireResult['status']);
        $this->assertEquals($fields['taskId'], $questionnaireResult['taskId']);
        $this->assertEquals($fields['activityId'], $questionnaireResult['activityId']);
    }

    public function testAddQuestionnaireResult()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $this->assertEquals(true, count($questionnaireResult));
    }

    public function testGetQuestionnaireResult()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->getQuestionnaireService()->getQuestionnaireResult($questionnaireResult['id']);

        $this->assertEquals($questionnaireResult['questionnaireId'], $questionnaireResult1['questionnaireId']);
        $this->assertEquals($questionnaireResult['userId'], $questionnaireResult1['userId']);
        $this->assertEquals($questionnaireResult['status'], $questionnaireResult1['status']);
        $this->assertEquals($questionnaireResult['taskId'], $questionnaireResult1['taskId']);
        $this->assertEquals($questionnaireResult['activityId'], $questionnaireResult1['activityId']);
    }

    /*public function testFinishTest()
    {
        $user = $this->getCurrentUser();
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->getQuestionnaireService()->finishTest($questionnaireResult['id'], array());
        $this->assertEquals('finished', $questionnaireResult1['status']);
    }*/

    public function testGetQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->getQuestionnaireService()->getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId(1, 1, 1);
        $this->assertEquals($questionnaireResult['userId'], $questionnaireResult1['userId']);
        $this->assertEquals($questionnaireResult['status'], $questionnaireResult1['status']);
        $this->assertEquals($questionnaireResult['taskId'], $questionnaireResult1['taskId']);
        $this->assertEquals($questionnaireResult['activityId'], $questionnaireResult1['activityId']);
    }

    public function testFindQuestionnaireResultsByQuestionnaireId()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->createQuestionnaireResult1();
        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireId($questionnaireResult['questionnaireId']);
        $this->assertEquals(2, count($questionnaireResults));
    }

    public function testDeleteQuestionnaireResultByTaskIds()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->createQuestionnaireResult1();
        $result = $this->getQuestionnaireService()->deleteQuestionnaireResultByTaskIds(array($questionnaireResult['taskId'], $questionnaireResult1['taskId']));
        $this->assertEquals(2, $result);
    }

    public function testFindQuestionnaireResultsByTaskIds()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->createQuestionnaireResult1();
        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByTaskIds(array($questionnaireResult['taskId'], $questionnaireResult1['taskId']));
        $this->assertEquals(2, count($questionnaireResults));
    }

    public function testDeleteDoingQuestionnaireByTaskIds()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->createQuestionnaireResult1();
        $result = $this->getQuestionnaireService()->deleteDoingQuestionnaireByTaskIds(array($questionnaireResult['taskId'], $questionnaireResult1['taskId']));
        $this->assertEquals(true, $result);
    }

    public function testFindQuestionnaireResultsByQuestionnaireIdAndStatus()
    {
        $questionnaireResult = $this->createQuestionnaireResult();
        $questionnaireResult1 = $this->createQuestionnaireResult1();
        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireResult['questionnaireId'], 'doing');
        $this->assertEquals(2, count($questionnaireResults));
    }

    protected function createQuestionnaire()
    {
        $question = array(
            'title' => 'bangbangyou',
            'courseSetId' => 1,
            'updatedUserId' => 1,
        );

        return $this->getQuestionnaireService()->createQuestionnaire($question);
    }

    protected function createQuestionnaireResult()
    {
        $fields = array(
            'questionnaireId' => 1,
            'userId' => 1,
            'status' => 'doing',
            'taskId' => 1,
            'activityId' => 1,
        );

        return $this->getQuestionnaireService()->addQuestionnaireResult($fields);
    }

    protected function createQuestionnaireResult1()
    {
        $fields = array(
            'questionnaireId' => 1,
            'userId' => 2,
            'status' => 'doing',
            'taskId' => 2,
            'activityId' => 2,
        );

        return $this->getQuestionnaireService()->addQuestionnaireResult($fields);
    }

    protected function createQuestionnaire1()
    {
        $question = array(
            'title' => 'bangbangyou1',
            'courseSetId' => 1,
            'updatedUserId' => 3,
        );

        return $this->getQuestionnaireService()->createQuestionnaire($question);
    }

    protected function getQuestionnaireService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }
}
