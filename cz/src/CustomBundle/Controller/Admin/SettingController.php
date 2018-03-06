<?php

namespace CustomBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Admin\SettingController as BaseSettingController;
use CustomBundle\Biz\Lesson\Job\TeachingAimWarningJob;

class SettingController extends BaseSettingController
{
    public function warningAction(Request $request)
    {
        $settingWarning = $this->getSettingService()->get('warning', array());

        $default = array(
        );

        $warning = array_merge($default, $settingWarning);

        if ($request->getMethod() === 'POST') {
            $warning = $request->request->all();

            $this->getSettingService()->set('warning', $warning);

            $this->getLogService()->info('system', 'update_settings', '更新预警设置', $warning);
            $this->setFlashMessage('success', 'site.save.success');
        }

        return $this->render('admin/system/warning-setting.html.twig', array(
            'warning' => $warning,
        ));
    }
}
