<?php

namespace AppBundle\Extensions\DataTag;

use Biz\File\Service\UploadFileService;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;

class AttachmentListDataTag extends BaseDataTag implements DataTag
{
    /**
     * 获取附件列表.
     *
     * @param array $arguments 参数
     *
     * @throws \Exception
     *
     * @return array 附件列表
     */
    public function getData(array $arguments)
    {
        if (!ArrayToolKit::requireds($arguments, array('targetType', 'targetId'))) {
            throw new \Exception('缺少参数，无法获取附件列表');
        }
        $type = 'attachment';
        $targetType = $arguments['targetType'];
        $targetId = $arguments['targetId'];

        $attachments = $this->getUploadFileService()->findUseFilesByTargetTypeAndTargetIdAndType($targetType, $targetId, $type);

        return ArrayToolkit::index($attachments, 'id');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return ServiceKernel::instance()->createService('File:UploadFileService');
    }
}
