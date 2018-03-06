<?php

namespace CustomBundle\Biz\Org\Service;

use Biz\Org\Service\OrgService as BaseOrgService;

interface OrgService extends BaseOrgService
{
    public function findOrgsByParentId($parentId);
}
