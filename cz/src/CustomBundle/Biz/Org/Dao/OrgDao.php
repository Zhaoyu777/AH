<?php

namespace CustomBundle\Biz\Org\Dao;

use Biz\Org\Dao\OrgDao as BaseOrgDao;

interface OrgDao extends BaseOrgDao
{
    public function findByParentId($parentId);
}
