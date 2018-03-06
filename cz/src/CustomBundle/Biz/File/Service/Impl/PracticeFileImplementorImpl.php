<?php

namespace CustomBundle\Biz\File\Service\Impl;

use Biz\BaseService;
use Biz\File\Service\FileImplementor;
use AppBundle\Common\FileToolkit;

class PracticeFileImplementorImpl extends BaseService
{
    protected $filename;
    public function initUpload($targetType, $targetId, $filePath)
    {
        $params = $this->getParams($targetType, $targetId, $filePath);
        $params['targetId'] = $targetId;
        $params['createdUserId'] = $this->getCurrentUser()->id;
        $params['createdTime'] = time();

        return $params;
    }

    public function addFile($targetType, $targetId, $filePath)
    {
        $uploadFile = $this->getParams($targetType, $targetId, $filePath);

        $uploadFile['isPublic'] = 0;
        $uploadFile['canDownload'] = empty($uploadFile['canDownload']) ? 0 : 1;

        $uploadFile['updatedUserId'] = $uploadFile['createdUserId'] = $this->getCurrentUser()->id;
        $uploadFile['updatedTime'] = $uploadFile['createdTime'] = time();
        $targetPath = $this->getFilePath($targetType, $targetId);

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        copy($filePath, "{$targetPath}/{$this->filename}");

        return $uploadFile;
    }

    public function getParams($targetType, $targetId, $filePath)
    {
        try {
            $imagine = FileToolkit::createImagine();
            $image = $imagine->open($filePath);
        } catch (\Exception $e) {
            throw new \Exception('该文件为非图片格式文件，请重新上传。');
        }

        $imgBox = $image->getSize();

        $uploadFile = array();

        $uploadFile['filename'] = time();

        $uploadFile['ext'] = 'jpg';
        $uploadFile['fileSize'] = $imgBox->square();

        $uploadFile['filename'] = $this->filename = FileToolkit::generateFilename($uploadFile['ext']);

        $uploadFile['hashId'] = "{$targetType}/{$targetId}/{$this->filename}";

        $uploadFile['convertHash'] = "ch-{$uploadFile['hashId']}";
        $uploadFile['convertStatus'] = 'none';
        $uploadFile['targetType'] = $targetType;

        $uploadFile['type'] = 'image';
        $uploadFile['targetId'] = $targetId;

        return $uploadFile;
    }

    protected function getFilePath($targetType, $targetId)
    {
        $baseDirectory = $this->biz['topxia.disk.local_directory'];

        return $baseDirectory.DIRECTORY_SEPARATOR.$targetType.DIRECTORY_SEPARATOR.$targetId;
    }
}
