<?php

namespace AppBundle\Controller\Admin;

use Biz\System\Service\SettingService;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Component\OAuthClient\OAuthClientFactory;

class UserSettingController extends BaseController
{
    public function authAction(Request $request)
    {
        $auth = $this->getSettingService()->get('auth', array());
        $defaultSettings = $this->getSettingService()->get('default', array());
        $userDefaultSetting = $this->getSettingService()->get('user_default', array());
        $courseDefaultSetting = $this->getSettingService()->get('course_default', array());
        $path = $this->container->getParameter('kernel.root_dir').'/../web/assets/img/default/';
        $userDefaultSet = $this->getUserDefaultSet();
        $defaultSetting = array_merge($userDefaultSet, $userDefaultSetting);

        $default = array(
            'register_mode' => 'closed',
            'email_enabled' => 'closed',
            'setting_time' => -1,
            'email_activation_title' => '',
            'email_activation_body' => '',
            'welcome_enabled' => 'closed',
            'welcome_sender' => '',
            'welcome_methods' => array(),
            'welcome_title' => '',
            'welcome_body' => '',
            'user_terms' => 'closed',
            'user_terms_body' => '',
            'captcha_enabled' => 0,
            'register_protective' => 'none',
            'nickname_enabled' => 0,
            'avatar_alert' => 'none',
        );

        if (isset($auth['captcha_enabled']) && $auth['captcha_enabled']) {
            if (!isset($auth['register_protective'])) {
                $auth['register_protective'] = 'low';
            }
        }

        $auth = array_merge($default, $auth);

        if ($request->getMethod() == 'POST') {
            $defaultSetting = $request->request->all();

            if (!isset($defaultSetting['user_name'])) {
                $defaultSetting['user_name'] = '学员';
            }

            $userDefaultSetting = ArrayToolkit::parts($defaultSetting, array(
                'defaultAvatar', 'user_name',
            ));

            $default = $this->getSettingService()->get('default', array());
            $defaultSetting = array_merge($default, $defaultSettings, $courseDefaultSetting, $userDefaultSetting);

            $this->getSettingService()->set('user_default', $userDefaultSetting);
            $this->getSettingService()->set('default', $defaultSetting);

            if (isset($auth['setting_time']) && $auth['setting_time'] > 0) {
                $firstSettingTime = $auth['setting_time'];
                $authUpdate = $request->request->all();
                $authUpdate['setting_time'] = $firstSettingTime;
            } else {
                $authUpdate = $request->request->all();
                $authUpdate['setting_time'] = time();
            }

            if (empty($authUpdate['welcome_methods'])) {
                $authUpdate['welcome_methods'] = array();
            }

            if ($authUpdate['register_protective'] == 'none') {
                $authUpdate['captcha_enabled'] = 0;
            } else {
                $authUpdate['captcha_enabled'] = 1;
            }

            $auth = array_merge($auth, $authUpdate);
            $this->getSettingService()->set('auth', $auth);

            $this->getLogService()->info('system', 'update_settings', '更新注册设置', $auth);
            $this->setFlashMessage('success', 'site.save.success');
        }

        $userFields = $this->getUserFieldService()->getEnabledFieldsOrderBySeq();

        return $this->render('admin/system/auth.html.twig', array(
            'auth' => $auth,
            'userFields' => $userFields,
            'defaultSetting' => $defaultSetting,
            'hasOwnCopyright' => false,
        ));
    }

    public function userAvatarAction(Request $request)
    {
        $defaultSetting = $this->getSettingService()->get('default', array());

        if ($request->getMethod() == 'POST') {
            $userDefaultSetting = $request->request->all();

            $userDefaultSetting = ArrayToolkit::parts($userDefaultSetting, array(
                'defaultAvatar',
            ));

            $defaultSetting = array_merge($defaultSetting, $userDefaultSetting);

            $this->getSettingService()->set('default', $defaultSetting);

            $this->getLogService()->info('system', 'update_settings', '更新头像设置', $userDefaultSetting);
            $this->setFlashMessage('success', 'site.save.success');
        }

        return $this->render('admin/system/user-avatar.html.twig', array(
            'defaultSetting' => $defaultSetting,
        ));
    }

    public function loginConnectAction(Request $request)
    {
        $clients = OAuthClientFactory::clients();
        $default = $this->getDefaultLoginConnect($clients);
        $loginConnect = $this->getSettingService()->get('login_bind', array());
        $loginConnect = array_merge($default, $loginConnect);

        if ($request->isMethod('POST')) {
            $loginConnect = $request->request->all();
            $loginConnect = ArrayToolkit::trim($loginConnect);
            $loginConnect = $this->decideEnabledLoginConnect($loginConnect);
            $this->getSettingService()->set('login_bind', $loginConnect);
            $this->getLogService()->info('system', 'update_settings', '更新登录设置', $loginConnect);
            $this->updateWeixinMpFile($loginConnect['weixinmob_mp_secret']);
        }

        return $this->render('admin/system/login-connect.html.twig', array(
            'loginConnect' => $loginConnect,
            'clients' => $clients,
        ));
    }

    public function userCenterAction(Request $request)
    {
        $setting = $this->getSettingService()->get('user_partner', array());

        $default = array(
            'mode' => 'default',
            'nickname_enabled' => 0,
            'avatar_alert' => 'none',
            'email_filter' => '',
            'partner_config' => array(
                'discuz' => array(),
                'phpwind' => array(
                    'conf' => array(),
                    'database' => array(),
                ),
            ),
        );

        $setting = array_merge($default, $setting);

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $data['email_filter'] = trim(str_replace(array("\n\r", "\r\n", "\r"), "\n", $data['email_filter']));
            $setting['mode'] = $data['mode'];
            $setting['email_filter'] = $data['email_filter'];

            $setting['partner_config']['discuz'] = $data['discuzConfig'];

            if ($setting['mode'] == 'phpwind') {
                $setting['partner_config']['phpwind'] = $data['phpwind_config'];
                $phpwindConfig = $data['phpwind_config'];
                $configDirectory = $this->getParameter('kernel.root_dir').'/config/';
                $phpwindConfigPath = $configDirectory.'windid_client_config.php';
                if (!file_exists($phpwindConfigPath) || !is_writable($phpwindConfigPath)) {
                    $this->setFlashMessage('danger', $this->get('translator')->trans('admin.user_center.user_center.config_error', array('%path%' => $phpwindConfigPath)));
                    goto response;
                }

                file_put_contents($phpwindConfigPath, $phpwindConfig);
            }

            $this->getSettingService()->set('user_partner', $setting);
            $this->getLogService()->info('system', 'setting_userCenter', '用户中心设置', $setting);
            $this->setFlashMessage('success', 'site.save.success');
        }

        response:
        $discuzConfig = $setting['partner_config']['discuz'];
        $phpwindConfig = empty($setting['partner_config']['phpwind']) ? '' : $setting['partner_config']['phpwind'];

        return $this->render('admin/system/user-center.html.twig', array(
            'setting' => $setting,
            'discuzConfig' => $discuzConfig,
            'phpwindConfig' => $phpwindConfig,
        ));
    }

    public function userFieldsAction(Request $request)
    {
        $textCount = $this->getUserFieldService()->countFields(array('fieldName' => 'textField'));
        $intCount = $this->getUserFieldService()->countFields(array('fieldName' => 'intField'));
        $floatCount = $this->getUserFieldService()->countFields(array('fieldName' => 'floatField'));
        $dateCount = $this->getUserFieldService()->countFields(array('fieldName' => 'dateField'));
        $varcharCount = $this->getUserFieldService()->countFields(array('fieldName' => 'varcharField'));

        $fields = $this->getUserFieldService()->getFieldsOrderBySeq();

        for ($i = 0; $i < count($fields); ++$i) {
            if (strstr($fields[$i]['fieldName'], 'textField')) {
                $fields[$i]['fieldName'] = '多行文本';
            }

            if (strstr($fields[$i]['fieldName'], 'varcharField')) {
                $fields[$i]['fieldName'] = '文本';
            }

            if (strstr($fields[$i]['fieldName'], 'intField')) {
                $fields[$i]['fieldName'] = '整数';
            }

            if (strstr($fields[$i]['fieldName'], 'floatField')) {
                $fields[$i]['fieldName'] = '小数';
            }

            if (strstr($fields[$i]['fieldName'], 'dateField')) {
                $fields[$i]['fieldName'] = '日期';
            }
        }

        $courseSetting = $this->getSettingService()->get('course', array());
        $auth = $this->getSettingService()->get('auth', array());

        $commomFields = $this->get('codeages_plugin.dict_twig_extension')->getDict('userInfoFields');
        $commomFieldsKeys = array_keys($commomFields);

        if (isset($auth['registerFieldNameArray'])) {
            $auth['registerFieldNameArray'] = array_unique(array_merge($auth['registerFieldNameArray'], $commomFieldsKeys));
        }

        if (isset($courseSetting['userinfoFieldNameArray'])) {
            $courseSetting['userinfoFieldNameArray'] = array_unique(array_merge($courseSetting['userinfoFieldNameArray'], $commomFieldsKeys));
        }

        $userPartner = $this->getSettingService()->get('user_partner', array());
        $userFields = $this->getUserFieldService()->getEnabledFieldsOrderBySeq();
        $userFields = ArrayToolkit::index($userFields, 'fieldName');

        if ($request->getMethod() == 'POST') {
            $courseSetting['buy_fill_userinfo'] = $request->request->get('buy_fill_userinfo');
            $courseSetting['userinfoFields'] = $request->request->get('userinfoFields');
            $courseSetting['userinfoFieldNameArray'] = $request->request->get('userinfoFieldNameArray');

            $this->getSettingService()->set('course', $courseSetting);

            $userPartner['avatar_alert'] = $request->request->get('avatar_alert');
            $userPartner['nickname_enabled'] = $request->request->get('nickname_enabled');
            $this->getSettingService()->set('user_partner', $userPartner);

            $auth['fill_userinfo_after_login'] = $request->request->get('fill_userinfo_after_login');
            $auth['registerSort'] = $request->request->get('registerSort');
            $auth['registerFieldNameArray'] = $request->request->get('registerFieldNameArray');

            $cloudSmsSettings = $this->getSettingService()->get('cloud_sms', array('sms_enabled' => 0));
            $mobileSmsValidate = $request->request->get('mobileSmsValidate', 0);
            $auth['mobileSmsValidate'] = $cloudSmsSettings['sms_enabled'] && $mobileSmsValidate ? $mobileSmsValidate : 0;

            $this->getSettingService()->set('auth', $auth);

            $this->getLogService()->info('system', 'update_settings', '更新用户信息设置', $auth);
            $this->setFlashMessage('success', 'site.save.success');
        }

        return $this->render('admin/system/user-fields.html.twig', array(
            'textCount' => $textCount,
            'intCount' => $intCount,
            'floatCount' => $floatCount,
            'dateCount' => $dateCount,
            'varcharCount' => $varcharCount,
            'fields' => $fields,
            'courseSetting' => $courseSetting,
            'authSetting' => $auth,
            'userFields' => $userFields,
        ));
    }

    public function editUserFieldsAction(Request $request, $id)
    {
        $field = $this->getUserFieldService()->getField($id);

        if (empty($field)) {
            throw $this->createNotFoundException();
        }

        if (strstr($field['fieldName'], 'textField')) {
            $field['fieldName'] = '多行文本';
        }

        if (strstr($field['fieldName'], 'varcharField')) {
            $field['fieldName'] = '文本';
        }

        if (strstr($field['fieldName'], 'intField')) {
            $field['fieldName'] = '整数';
        }

        if (strstr($field['fieldName'], 'floatField')) {
            $field['fieldName'] = '小数';
        }

        if (strstr($field['fieldName'], 'dateField')) {
            $field['fieldName'] = '日期';
        }

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            if (isset($fields['enabled'])) {
                $fields['enabled'] = 1;
            } else {
                $fields['enabled'] = 0;
            }

            $field = $this->getUserFieldService()->updateField($id, $fields);
            $this->changeUserInfoFields($field, $type = 'update');

            return $this->redirect($this->generateUrl('admin_setting_user_fields'));
        }

        return $this->render('admin/system/user-fields.modal.edit.html.twig', array(
            'field' => $field,
        ));
    }

    public function deleteUserFieldsAction(Request $request, $id)
    {
        $field = $this->getUserFieldService()->getField($id);

        if (empty($field)) {
            throw $this->createNotFoundException();
        }

        if ($request->getMethod() == 'POST') {
            $this->changeUserInfoFields($field, $type = 'delete');

            $this->getUserFieldService()->dropField($id);

            return $this->redirect($this->generateUrl('admin_setting_user_fields'));
        }

        return $this->render('admin/system/user-fields.modal.delete.html.twig', array(
            'field' => $field,
        ));
    }

    public function addUserFieldsAction(Request $request)
    {
        $field = $request->request->all();

        if (isset($field['field_title'])
            && in_array($field['field_title'], array('真实姓名', '手机号码', 'QQ', '所在公司', '身份证号码', '性别', '职业', '微博', '微信'))) {
            throw $this->createAccessDeniedException('请勿添加与默认字段相同的自定义字段！');
        }

        $field = $this->getUserFieldService()->addUserField($field);

        $this->changeUserInfoFields($field, $type = 'update');

        if ($field == false) {
            $this->setFlashMessage('danger', 'admin.setting.user.custom_fileds.empty');
        }

        return $this->redirect($this->generateUrl('admin_setting_user_fields'));
    }

    protected function updateWeixinMpFile($val)
    {
        $dir = realpath(__DIR__.'/../../../../web/');
        array_map('unlink', glob($dir.'/MP_verify_*.txt'));
        if (!empty($val)) {
            file_put_contents($dir.'/MP_verify_'.$val.'.txt', $val);
        }
    }

    protected function getUserDefaultSet()
    {
        $default = array(
            'defaultAvatar' => 0,
            'defaultAvatarFileName' => 'avatar',
            'articleShareContent' => '我正在看{{articletitle}}，关注{{sitename}}，分享知识，成就未来。',
            'courseShareContent' => '我正在学习{{course}}，收获巨大哦，一起来学习吧！',
            'groupShareContent' => '我在{{groupname}}小组,发表了{{threadname}},很不错哦,一起来看看吧!',
            'classroomShareContent' => '我正在学习{{classroom}}，收获巨大哦，一起来学习吧！',
            'user_name' => '学员',
        );

        return $default;
    }

    private function changeUserInfoFields($fieldInfo, $type = 'update')
    {
        $auth = $this->getSettingService()->get('auth', array());
        $courseSetting = $this->getSettingService()->get('course', array());

        if (isset($auth['registerFieldNameArray'])) {
            if ($type == 'delete' || ($type == 'update' && !$fieldInfo['enabled'])) {
                foreach ($auth['registerFieldNameArray'] as $key => $value) {
                    if ($value == $fieldInfo['fieldName']) {
                        unset($auth['registerFieldNameArray'][$key]);
                    }
                }
            } elseif ($type == 'update' && $fieldInfo['enabled']) {
                $auth['registerFieldNameArray'][] = $fieldInfo['fieldName'];
                $auth['registerFieldNameArray'] = array_unique($auth['registerFieldNameArray']);
            }
        }

        if (isset($courseSetting['userinfoFieldNameArray'])) {
            if ($type == 'delete' || ($type == 'update' && !$fieldInfo['enabled'])) {
                foreach ($courseSetting['userinfoFieldNameArray'] as $key => $value) {
                    if ($value == $fieldInfo['fieldName']) {
                        unset($courseSetting['userinfoFieldNameArray'][$key]);
                    }
                }
            } elseif ($type == 'update' && $fieldInfo['enabled']) {
                $courseSetting['userinfoFieldNameArray'][] = $fieldInfo['fieldName'];
                $courseSetting['userinfoFieldNameArray'] = array_unique($courseSetting['userinfoFieldNameArray']);
            }
        }

        $this->getSettingService()->set('auth', $auth);
        $this->getSettingService()->set('course', $courseSetting);

        return true;
    }

    private function getDefaultLoginConnect($clients)
    {
        $default = array(
            'login_limit' => 0,
            'enabled' => 0,
            'verify_code' => '',
            'captcha_enabled' => 0,
            'temporary_lock_enabled' => 0,
            'temporary_lock_allowed_times' => 5,
            'ip_temporary_lock_allowed_times' => 20,
            'temporary_lock_minutes' => 20,
        );

        foreach ($clients as $type => $client) {
            $default["{$type}_enabled"] = 0;
            $default["{$type}_key"] = '';
            $default["{$type}_secret"] = '';
            $default["{$type}_set_fill_account"] = 0;
            if ($type == 'weixinmob') {
                $default['weixinmob_mp_secret'] = '';
            }
        }

        return $default;
    }

    private function decideEnabledLoginConnect($loginConnect)
    {
        if ($loginConnect['enabled'] == 0) {
            $loginConnect['weibo_enabled'] = 0;
            $loginConnect['qq_enabled'] = 0;
            $loginConnect['renren_enabled'] = 0;
            $loginConnect['weixinweb_enabled'] = 0;
            $loginConnect['weixinmob_enabled'] = 0;
        }
        //新增第三方登录方式，加入下列列表计算，以便判断是否关闭第三方登录功能
        $loginConnects = ArrayToolkit::parts($loginConnect, array('weibo_enabled', 'qq_enabled', 'renren_enabled', 'weixinweb_enabled', 'weixinmob_enabled'));
        $sum = 0;
        foreach ($loginConnects as $value) {
            $sum += $value;
        }

        if ($sum < 1) {
            if ($loginConnect['enabled'] == 1) {
                $this->setFlashMessage('danger', 'site.third_party.login.way.no_choose');
            }
            if ($loginConnect['enabled'] == 0) {
                $this->setFlashMessage('success', 'site.save.success');
            }
            $loginConnect['enabled'] = 0;
        } else {
            $loginConnect['enabled'] = 1;
            $this->setFlashMessage('success', 'site.save.success');
        }

        return $loginConnect;
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getAppService()
    {
        return $this->createService('CloudPlatform:AppService');
    }

    /**
     * @return SettingService
     */
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
}
