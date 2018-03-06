<?php

namespace AppBundle\Extensions\DataTag\Test;

use Biz\BaseTestCase;
use AppBundle\Extensions\DataTag\CourseRankByRatingDataTag;

class CourseRankByRatingDataTagTest extends BaseTestCase
{
    public function testGetData()
    {
        $datatag = new CourseRankByRatingDataTag();
        $courses = $datatag->getData(array('count' => 5));

        $this->assertEquals(0, count($courses));
    }
}
