<?php

namespace Tests\Unit\CustomBundle\Activity;

use Biz\BaseTestCase;

class RollcallResultServiceTest extends BaseTestCase
{
    public function testCreateResult()
    {
        $result = $this->mockResult();

        $created = $this->getRollcallResultService()->createResult($result);
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

        $created = $this->getRollcallResultService()->createResult($result);
    }

    public function testGetRollCallResult()
    {
        $result = $this->mockResult();

        $created = $this->getRollcallResultService()->createResult($result);
        $result = $this->getRollcallResultService()->getResult($created['id']);

        $this->assertEquals($created, $result);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testRemarkResultWhileNotExist()
    {
        $result = $this->mockResult();

        $created = $this->getRollcallResultService()->createResult($result);
        $fields = array(
            'score' => 1,
            'remark' => array('回答的很好', '很好'),
        );
        $this->getRollcallResultService()->remarkResult($created['id'] + 1, $fields);
    }

    public function testRemarkResult()
    {
        $result = $this->mockResult();

        $created = $this->getRollcallResultService()->createResult($result);
        $fields = array(
            'score' => 1,
            'remark' => array('回答的很好', '很好'),
        );
        $affected = $this->getRollcallResultService()->remarkResult($created['id'], $fields);

        $this->assertEquals($affected['remark'], $fields['remark']);
        $this->assertEquals($affected['score'], $fields['score']);
    }

    protected function getRollcallResultService()
    {
        return $this->createService('CustomBundle:Activity:RollcallResultService');
    }

    public function mockResult($fields = array())
    {
        $default = array(
            'activityId' => 1,
            'courseId' => 1,
            'courseTaskId' => 1,
            'userId' => 1,
        );

        return array_merge($default, $fields);
    }
}
