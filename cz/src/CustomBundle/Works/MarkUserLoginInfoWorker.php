<?php

namespace CustomBundle\Works;

use Codeages\Plumber\IWorker;

class MarkUserLoginInfoWorker extends AbstractWorker
{
    public function executeProcess($data)
    {
        $this->getUserService()->markWeixinLoginInfoProcess($data['body']['userId'], $data['body']);

        return array('code' => IWorker::FINISH);
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
