<?php

namespace CustomBundle\Biz\User\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\BaseService;

class CzieStudentServiceImpl extends BaseService
{
    public function searchStudents($conditions, $orderby, $start, $limit)
    {
        return $this->getStudentDao()->search($conditions, $orderby, $start, $limit);
    }

    public function findStudentsByUserIds($userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->getStudentDao()->findByUserIds($userIds);
    }

    public function countStudents($conditions)
    {
        return $this->getStudentDao()->count($conditions);
    }

    protected function getStudentDao()
    {
        return $this->createDao('CustomBundle:User:StudentDao');
    }
}
