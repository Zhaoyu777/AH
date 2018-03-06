<?php

namespace CustomBundle\Biz\Org\Dao\Impl;

use CustomBundle\Biz\Org\Dao\OrgDao;
use Biz\Org\Dao\Impl\OrgDaoImpl as BaseOrgDaoImpl;

class OrgDaoImpl extends BaseOrgDaoImpl implements OrgDao
{
    public function findByParentId($parentId)
    {
        return $this->findByFields(array('parentId' => $parentId));
    }
}
