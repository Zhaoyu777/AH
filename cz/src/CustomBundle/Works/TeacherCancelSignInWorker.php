<?php

namespace CustomBundle\Works;

use Codeages\Plumber\IWorker;
use Codeages\Biz\Framework\Event\Event;

class TeacherCancelSignInWorker extends AbstractWorker
{
    public function executeProcess($data)
    {
        $this->dispatchEvent('signIn.cancel', new Event($data['body']['signIn']));

        return array('code' => IWorker::FINISH);
    }
}
