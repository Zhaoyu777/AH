<?php

namespace Tests\Unit\OrderFacade;

use Biz\Accessor\AccessorInterface;
use Biz\BaseTestCase;
use Biz\OrderFacade\Product\CourseProduct;

class CourseProductTest extends BaseTestCase
{
    public function testValidate()
    {
        $courseProduct = new CourseProduct();
        $courseProduct->setBiz($this->getBiz());

        $this->mockBiz('Course:CourseService', array(
            array('functionName' => 'getCourse', 'returnValue' => array('buyable' => true)),
            array('functionName' => 'canJoinCourse', 'returnValue' => array('code' => AccessorInterface::SUCCESS)),
        ));

        $this->assertEquals(null, $courseProduct->validate());
    }

    /**
     * @expectedException  \Biz\OrderFacade\Exception\OrderPayCheckException;
     */
    public function testValidateOnErrorWhenCourseUnPurchasable()
    {
        $courseProduct = new CourseProduct();
        $courseProduct->setBiz($this->getBiz());

        $this->mockBiz('Course:CourseService', array(
            array('functionName' => 'getCourse', 'returnValue' => array('buyable' => false)),
            array('functionName' => 'canJoinCourse', 'returnValue' => array('code' => AccessorInterface::SUCCESS)),
        ));
    }

    /**
     * @expectedException \Biz\OrderFacade\Exception\OrderPayCheckException
     */
    public function testValidateWithError()
    {
        $courseProduct = new CourseProduct();
        $courseProduct->setBiz($this->getBiz());

        $this->mockBiz('Course:CourseService', array(
            array('functionName' => 'getCourse', 'returnValue' => array('buyable' => true)),
            array('functionName' => 'canJoinCourse', 'returnValue' => array('code' => 'error', 'msg' => 'wrong')),
        ));

        $courseProduct->validate();
    }
}
