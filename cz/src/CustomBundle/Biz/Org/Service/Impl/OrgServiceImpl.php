<?php

namespace CustomBundle\Biz\Org\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Org\Service\OrgService;
use Biz\Org\Service\Impl\OrgServiceImpl as BaseOrgServiceImpl;

class OrgServiceImpl extends BaseOrgServiceImpl implements OrgService
{
    public function findOrgsByParentId($parentId)
    {
        return $this->getOrgDao()->findByParentId($parentId);
    }

    public function createFacultyLeader($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('orgId', 'orgCode', 'userId'))) {
            throw $this->createServiceException('缺少必要字段,添加失败');
        }

        $fields = ArrayToolkit::parts($fields, array(
            'orgId',
            'orgCode',
            'userId',
        ));

        return $this->getLeaderDao()->create($fields);
    }

    public function findFacultyLeadersByOrgId($orgId)
    {
        return $this->getLeaderDao()->findByOrgId($orgId);
    }

    protected function getLeaderDao()
    {
        return $this->createDao('CustomBundle:Org:LeaderDao');
    }

    public function getOrgDao()
    {
        return $this->createDao('CustomBundle:Org:OrgDao');
    }
}
