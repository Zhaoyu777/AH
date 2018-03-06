<?php

namespace Tests\Unit\CustomBundle\DisplayWall;

use Biz\BaseTestCase;

class ResultServiceTest extends BaseTestCase
{
    public function testCreateResult()
    {
        $result = $this->mockResult();

        $created = $this->getDisplayWallResultService()->createResult($result);
        $this->assertEquals($created['activityId'], $result['activityId']);
        $this->assertEquals($created['courseId'], $result['courseId']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateResultWithoutRequiredFields()
    {
        $result = array(
            'activityId' => 1,
            'courseId' => 1,
            'courseTaskId' => 1,
        );

        $created = $this->getDisplayWallResultService()->createResult($result);
    }

    public function testGetRollCallResult()
    {
        $result = $this->mockResult();

        $created = $this->getDisplayWallResultService()->createResult($result);
        $result = $this->getDisplayWallResultService()->getResult($created['id']);

        $this->assertEquals($created, $result);
    }

    public function testFindResultsByActivityId()
    {
        $result1 = $this->mockResult();
        $created1 = $this->getDisplayWallResultService()->createResult($result1);

        $result2 = $this->mockResult(array('activityId' => 2));
        $created2 = $this->getDisplayWallResultService()->createResult($result2);

        $result3 = $this->mockResult(array('courseId' => 2));
        $created3 = $this->getDisplayWallResultService()->createResult($result3);

        $results = $this->getDisplayWallResultService()->findResultsByActivityId(1);

        $this->assertEquals(count($results), 2);
        $this->assertContains($created1, $results);
        $this->assertContains($created3, $results);
    }

    protected function getDisplayWallResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }

    public function mockResult($fields = array())
    {
        $default = array(
            'activityId' => 1,
            'courseId' => 1,
            'courseTaskId' => 1,
            'userId' => 1,
            'uri' => '/fiels/test.jpg',
        );

        return array_merge($default, $fields);
    }

    public function testCreateContent()
    {
        $content = $this->mockContent();

        $created = $this->getDisplayWallResultService()->createContent($content);

        $this->assertEquals($created['resultId'], $content['resultId'], $content['uri']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateContentWithoutRequiredFields()
    {
        $this->getDisplayWallResultService()->createContent(array());
    }

    public function testGetContent()
    {
        $content = $this->mockContent();

        $created = $this->getDisplayWallResultService()->createContent($content);
        $content = $this->getDisplayWallResultService()->getContent($created['id']);

        $this->assertEquals($created, $content);
    }

    public function mockContent($fields = array())
    {
        $result = $this->mockResult();
        $result = $this->getDisplayWallResultService()->createResult($result);
        $default = array(
            'resultId' => $result['id'],
            'uri' => 'files/test.jpg',
        );

        return array_merge($default, $fields);
    }
}
