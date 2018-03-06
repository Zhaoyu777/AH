<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class CzieOrgsDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (!empty($arguments['role']) && $arguments['role'] == 'student') {
            $faculties = $this->getFacultyService()->findAllFaculties();

            return ArrayToolkit::index($faculties, 'code');
        }
        $teachOrg = $this->getOrgService()->getOrgByCode('300');

        $orgs = $this->getOrgService()->findOrgsByParentId($teachOrg['id']);

        return ArrayToolkit::index($orgs, 'id');
    }

    protected function getOrgService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Org:OrgService');
    }

    protected function getFacultyService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:User:FacultyService');
    }
}
