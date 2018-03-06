<?php

namespace Tests\Unit\CustomBundle\GenericData;

use Biz\BaseTestCase;

class GenericDataServiceTest extends BaseTestCase
{
    public function testCreateData()
    {
        $data = $this->mockData();

        $created = $this->getGenericDataService()->createData($data);

        $this->assertEquals($data['type'], $created['type']);
        $this->assertEquals($data['data'], $created['data']);
    }

    public function testCreateDataWhileRemainedTimes()
    {
        $data = $this->mockData();
        $data['times'] = 10;

        $created = $this->getGenericDataService()->createData($data);

        $this->assertEquals($data['type'], $created['type']);
        $this->assertEquals($data['data'], $created['data']);
        $this->assertEquals($data['times'], $created['remainedTimes']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateDataLackRequiredFileds()
    {
        $data = $this->mockData();

        unset($data['type']);

        $this->getGenericDataService()->createData($data);
    }

    public function testDestoryData()
    {
        $data = $this->mockData();

        $created = $this->getGenericDataService()->createData($data);

        $deleted = $this->getGenericDataService()->destroyData($data['type']);

        $this->assertNULL($deleted);

        $this->getGenericDataService()->destroyData($data['type']);
    }

    public function testGetDataByType()
    {
        $data = $this->mockData();

        $created = $this->getGenericDataService()->createData($data);

        $genericData = $this->getGenericDataService()->getDataByType($data['type']);

        $this->assertEquals($created, $genericData);
    }

    public function testGetDataByTypeWhileExpired()
    {
        $data = $this->mockData();
        $data['expiredTime'] = time() - 2;

        $created = $this->getGenericDataService()->createData($data);

        $genericData = $this->getGenericDataService()->getDataByType($data['type']);

        $this->assertNULL($genericData);
    }

    protected function mockData()
    {
        $data = array(
            'type' => 'weixin_token',
            'data' => array('access_token' => 'fafasdfasdfasdfasdfads'),
            'times' => 0,
            'expiredTime' => 0,
        );

        return $data;
    }

    protected function getGenericDataService()
    {
        return $this->createService('CustomBundle:GenericData:GenericDataService');
    }
}
