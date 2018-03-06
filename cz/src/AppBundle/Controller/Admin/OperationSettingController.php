<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class OperationSettingController extends BaseController
{
    public function wapSetAction(Request $request)
    {
        $defaultWapSetting = array(
            'enabled' => 1,
        );

        if ($request->isMethod('POST')) {
            $wapSetting = $request->request->all();
            $wapSetting = ArrayToolkit::parts($wapSetting, array(
                'enabled',
            ));

            $wapSetting = array_merge($defaultWapSetting, $wapSetting);
            $this->getSettingService()->set('wap', $wapSetting);
            $this->getLogService()->info('system', 'update_settings', '更新WAP设置', $wapSetting);
            $this->setFlashMessage('success', 'site.save.success');
        }

        $wapSetting = $this->setting('wap', array());
        $wapSetting = array_merge($defaultWapSetting, $wapSetting);

        return $this->render('admin/wap/set.html.twig', array(
            'wapSetting' => $wapSetting,
        ));
    }

    public function articleSetAction(Request $request)
    {
        $articleSetting = $this->getSettingService()->get('article', array());

        $default = array(
            'name' => '资讯频道',
            'pageNums' => 20,
        );

        $articleSetting = array_merge($default, $articleSetting);

        if ($request->getMethod() == 'POST') {
            $articleSetting = $request->request->all();
            $this->getSettingService()->set('article', $articleSetting);
            $this->getLogService()->info('article', 'update_settings', '更新资讯频道设置', $articleSetting);
            $this->setFlashMessage('success', 'site.save.success');
        }

        return $this->render('admin/article/setting.html.twig', array(
            'articleSetting' => $articleSetting,
        ));
    }

    public function groupSetAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $set = $request->request->all();

            $this->getSettingService()->set('group', $set);
            $this->setFlashMessage('success', 'site.save.success');
        }

        return $this->render('admin/group/set.html.twig', array(
        ));
    }

    public function inviteSetAction(Request $request)
    {
        $default = array(
            'invite_code_setting' => 0,
            'promoted_user_value' => '',
            'promote_user_value' => '',
            'get_coupon_setting' => 1,
            'deadline' => 90,
            'inviteInfomation_template' => '{{registerUrl}}',
        );

        if ($request->getMethod() == 'POST') {
            $inviteSetting = $request->request->all();
            if (isset($inviteSetting['get_coupon_setting'])) {
                $inviteSetting['get_coupon_setting'] = 1;
            } else {
                $inviteSetting['get_coupon_setting'] = 0;
            }

            $inviteSetting = ArrayToolkit::parts($inviteSetting, array(
                'invite_code_setting',
                'promoted_user_value',
                'promote_user_value',
                'get_coupon_setting',
                'deadline',
                'inviteInfomation_template',
            ));

            $inviteSetting = array_merge($default, $inviteSetting);

            $this->getSettingService()->set('invite', $inviteSetting);
            $this->setFlashMessage('success', 'site.save.success');
            goto response;
        }

        $inviteSetting = $this->getSettingService()->get('invite', array());
        $inviteSetting = array_merge($default, $inviteSetting);

        response:
        return $this->render('admin/invite/set.html.twig', array(
            'inviteSetting' => $inviteSetting,
            'inviteInfomation_template' => $inviteSetting['inviteInfomation_template'],
        ));
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getAppService()
    {
        return $this->createService('CloudPlatform:AppService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function getUserFieldService()
    {
        return $this->createService('User:UserFieldService');
    }

    protected function getAuthService()
    {
        return $this->createService('User:AuthService');
    }

    protected function getArticleService()
    {
        return $this->createService('Article:ArticleService');
    }
}
