<?php

namespace CustomBundle\Biz\Api\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;

class SyncDataJob extends AbstractJob
{
    public function execute()
    {
        $params = $this->args;
        $this->getCzieSyncDataService()->execJob($params['jobId']);
    }

    protected function getCzieSyncDataService()
    {
        return $this->biz->service('CustomBundle:Api:CzieSyncDataService');
    }
}
