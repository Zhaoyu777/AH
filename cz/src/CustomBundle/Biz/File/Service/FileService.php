<?php

namespace CustomBundle\Biz\File\Service;

use Biz\Content\Service\FileService as BaseTaskService;

interface FileService extends BaseTaskService
{
    public function createFile($group, $file);
}
