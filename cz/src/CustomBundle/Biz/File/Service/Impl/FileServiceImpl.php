<?php

namespace CustomBundle\Biz\File\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\Content\Service\Impl\FileServiceImpl as BaseCourseServiceImpl;

class FileServiceImpl extends BaseCourseServiceImpl
{
    public function createFile($group, $file)
    {
        if (!ArrayToolkit::requireds($file, array('size', 'uri'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $file = ArrayToolkit::parts($file, array(
            'size',
            'uri',
        ));
        $group = $this->getGroupDao()->getByCode($group);

        $record = array();
        $user = $this->getCurrentUser();
        $record['userId'] = empty($user) || !$user->isLogin() ? 0 : $user['id'];

        if (!empty($group)) {
            $record['groupId'] = $group['id'];
        }
        $record['mime'] = '';
        $record['size'] = $file['size'];
        $record['uri'] = $file['uri'];
        $record['createdTime'] = time();
        $record = $this->getFileDao()->create($record);

        return $record;
    }
}