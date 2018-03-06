<?php

namespace CustomBundle\Biz\User\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\BaseService;

class FacultyServiceImpl extends BaseService
{
    public function findAllFaculties()
    {
        return $this->getFacultyDao()->findAll();
    }

    public function getByName($name)
    {
        return $this->getFacultyDao()->getFacultyByName($name);
    }

    protected function getFacultyDao()
    {
        return $this->createDao('CustomBundle:User:FacultyDao');
    }
}
