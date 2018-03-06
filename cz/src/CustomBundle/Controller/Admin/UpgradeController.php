<?php

namespace CustomBundle\Controller\Admin;

use AppBundle\Controller\BaseController;
use Symfony\Component\Filesystem\Filesystem;

class UpgradeController extends BaseController
{
    public function indexAction()
    {
        return $this->render('admin/upgrade/index.html');
    }

    public function startAction()
    {
        $filePath = $this->getFile();

        $time = time() + 3 * 60 *60;

        file_put_contents($filePath, (string) $time);

        return $this->createJsonResponse(true);
    }

    public function endAction()
    {
        $filePath = $this->getFile();
        $fileSystem = new Filesystem();
        @$fileSystem->remove($filePath);

        return $this->createJsonResponse(true);
    }

    private function getFile()
    {
        return $this->getBiz()['kernel.root_dir'].'/data/'.'upgrade.lock';
    }
}
