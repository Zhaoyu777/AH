<?php

namespace CustomBundle\Biz\File\Service;

use Biz\File\Service\UploadFileService as BaseUploadFileService;

interface UploadFileService extends BaseUploadFileService
{
    public function countFileByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);
}
