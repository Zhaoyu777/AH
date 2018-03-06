<?php

namespace Biz\File\FireWall;

use Topxia\Service\Common\ServiceKernel;

class ClassroomFileFireWall extends BaseFireWall implements FireWallInterface
{
    public function canAccess($attachment)
    {
        $user = $this->getCurrentUser();
        if ($user->isAdmin()) {
            return true;
        }

        $targetTypes = explode('.', $attachment['targetType']);
        $type = array_pop($targetTypes);
        if ($type === 'thread') {
            $thread = $this->getThreadService()->getThread($attachment['targetId']);

            if ($user['id'] == $thread['userId']) {
                return true;
            }
            $classroom = $this->getClassroomService()->getClassroom($thread['targetId']);

            if (array_key_exists($user['id'], $classroom['teacherIds']) || $user['id'] = $classroom['headTeacherId']) {
                return true;
            }
        } elseif ($type === 'post') {
            $post = $this->getThreadService()->getPost($attachment['targetId']);
            if ($user['id'] == $post['userId']) {
                return true;
            }
            $thread = $this->getThreadService()->getThread($post['threadId']);
            if ($user['id'] == $thread['userId']) {
                return true;
            }
            $classroom = $this->getClassroomService()->getClassroom($thread['targetId']);
            if (array_key_exists($user['id'], $classroom['teacherIds'])) {
                return true;
            }
        }

        return false;
    }

    protected function getKernel()
    {
        return ServiceKernel::instance();
    }

    protected function getThreadService()
    {
        return $this->getKernel()->createService('Thread:ThreadService');
    }

    protected function getClassroomService()
    {
        return $this->getKernel()->createService('Classroom:ClassroomService');
    }
}
