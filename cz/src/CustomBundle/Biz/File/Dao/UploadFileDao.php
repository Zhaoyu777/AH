<?php

namespace CustomBundle\Biz\File\Dao;

use Biz\File\Dao\UploadFileDao as BaseUploadFileDao;

interface UploadFileDao extends BaseUploadFileDao
{
    public function countByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);
}
