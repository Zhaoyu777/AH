<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class CzieStudentOrgDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        $org = $this->getOrgService()->getOrgByOrgCode($arguments['orgCode']);

        $faculty = $this->getFacultyService()->getByName($org['name']);

        return $faculty['code'];
    }

    protected function getFacultyService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:User:FacultyService');
    }

    protected function getOrgService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Org:OrgService');
    }
}
