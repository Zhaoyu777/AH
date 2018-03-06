<?php

namespace AppBundle\Extensions\DataTag\Test;

use Biz\BaseTestCase;
use AppBundle\Extensions\DataTag\CoursesByCategoryIdDataTag;

class CoursesByCategoryIdDataTagTest extends BaseTestCase
{
    public function testGetData()
    {
        $datatag = new CoursesByCategoryIdDataTag();
        $datatag->getData(array('categoryId' => 1, 'count' => 5));
    }
}
