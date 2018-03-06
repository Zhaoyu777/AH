<?php

namespace CustomBundle\Works;

use Codeages\Plumber\IWorker;

class WeixinUploadWorker extends AbstractWorker
{
    public function executeProcess($data)
    {
        $this->getDisplayWallResultService()->uploadContentProcess($data['body']['taskId'], $data['body']['media_id'], $data['body']['userId']);

        return array('code' => IWorker::FINISH);
    }

    protected function getDisplayWallResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }
}
