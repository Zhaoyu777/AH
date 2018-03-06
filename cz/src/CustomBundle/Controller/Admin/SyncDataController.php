<?php

namespace CustomBundle\Controller\Admin;

use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class SyncDataController extends BaseController
{
    public function syncDataManageAction(Request $request)
    {
        $currentJob = $this->getSyncDataService()->getLastJob();

        if ($request->getMethod() == 'POST') {
            if (empty($currentJob) || $currentJob['status'] == 'fail' || $currentJob['status'] == 'succeed') {
                $currentJob = $this->getSyncDataService()->createSyncDataJob();
            }
        }

        return $this->render('admin/system/sync-data.html.twig', array(
            'currentJob' => $currentJob
        ));
    }

    protected function getSyncDataService()
    {
        return $this->createService('CustomBundle:Api:CzieSyncDataService');
    }
}
