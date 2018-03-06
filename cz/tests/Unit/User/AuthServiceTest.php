<?php

namespace Tests\Unit\User;

use AppBundle\Common\SimpleValidator;
use Biz\BaseTestCase;

// TODO

class AuthServiceTest extends BaseTestCase
{
    public function testRegisterWithTypeDefault()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));
        $this->assertEquals($user['email'], 'test@edusoho.com');
    }

    public function testRegisterWithOtherType()
    {
        $makeToken = $this->getUserService()->makeToken('discuz');
        $getToken = $this->getUserService()->getToken('discuz', $makeToken);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
            'token' => $getToken,
        ), 'discuz');
        $this->assertEquals($user['email'], 'test@edusoho.com');
    }

    //同步功能需要Discuz的安装支持，暂时不能测
    // public function testSyncLogin()
    // {
    //     $this->getSettingService()->set('user_partner',array('mode' => 'discuz'));
    //     $makeToken = $this->getUserService()->makeToken('discuz');
    //     $getToken = $this->getUserService()->getToken('discuz',$makeToken);
    //     $user = $this->getAuthService()->register(array(
    //         'email' => 'test@edusoho.com',
    //         'nickname' => 'test',
    //         'password' => '123456',
    //         'token' => $getToken,
    //     ),'discuz');

    //     $this->getAuthService()->syncLogin($user['id']);
    //     $this->getSettingService()->delete('user_partner');
    // }

    public function testChangeNickname()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $this->getAuthService()->changeNickname($user['id'], 'newName');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertEquals('newName', $newUser['nickname']);
    }

    public function testChangeEmail()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $this->getAuthService()->changeEmail($user['id'], '123456', 'newemail@edusoho.com');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertEquals('newemail@edusoho.com', $newUser['email']);
    }

    public function testChangePassword()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $this->getAuthService()->changePassword($user['id'], '123456', '654321');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertNotEquals($user['password'], $newUser['password']);
    }

    public function testChangePayPassword()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $this->getAuthService()->changePayPassword($user['id'], '123456', '930919');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertNotEquals($user['payPassword'], $newUser['payPassword']);
    }

    public function testChangePayPasswordWithoutLoginPassword()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $this->getAuthService()->changePayPasswordWithoutLoginPassword($user['id'], '930919');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertNotEquals($user['payPassword'], $newUser['payPassword']);
    }

    public function testRefillFormDataWithoutNicknameAndEmail()
    {
        $value = array('register_mode' => 'email_or_mobile');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'password' => '123456',
            'emailOrMobile' => '18989492142',
            'nickname' => 'testuser',
        ));
        $this->assertNotNull($user);
        $this->getSettingService()->delete('auth');
    }

    public function testCheckUserNameWithUnexistName()
    {
        $result = $this->getAuthService()->checkUserName('yyy');
        $this->assertEquals('success', $result[0]);
        $this->assertEquals('', $result[1]);
    }

    public function testCheckUserNameWithExistName()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $this->getAuthService()->checkUserName('test');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('名称已存在!', $result[1]);
    }

    // public function testCheckUserNameWithNumNickname()
    // {
    //     $result = SimpleValidator::nickname('11111111111');
    //     $this->assertEquals(false, $result);
    // }

    public function testCheckEmailWithUnexistEmail()
    {
        $result = $this->getAuthService()->checkEmail('test@yeah.net');
        $this->assertEquals('success', $result[0]);
        $this->assertEquals('', $result[1]);
    }

    public function testCheckEmailWithExistEmail()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $this->getAuthService()->checkEmail('test@edusoho.com');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('Email已存在!', $result[1]);
    }

    public function testCheckMobileWithUnexistMobile()
    {
        $this->getAuthService()->checkMobile('18989492142');
        // $this->assertEquals('success', $result[0]);
        // $this->assertEquals('', $result[1]);
    }

    public function testCheckMobileWithExistMobile()
    {
        $value = array('register_mode' => 'mobile');
        $this->getSettingService()->set('auth', $value);
        $this->getAuthService()->register(array(
            'password' => '123456',
            'mobile' => '18989492142',
            'nickname' => 'test',
        ));
        $result = $this->getAuthService()->checkMobile('18989492142');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('手机号码已存在!', $result[1]);
        $this->getSettingService()->delete('auth');
    }

    public function testCheckEmailOrMobileWithUnexistEmailOrMobile()
    {
        $result = $this->getAuthService()->checkEmailOrMobile('18989492142');
        // $this->assertEquals('success', $result[0]);
        // $this->assertEquals('', $result[1]);
    }

    public function testCheckEmailOrMobileWithExistMobile()
    {
        $value = array('register_mode' => 'email_or_mobile');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'password' => '123456',
            'emailOrMobile' => '18989492142',
            'nickname' => 'test',
        ));
        $result = $this->getAuthService()->checkEmailOrMobile('18989492142');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('手机号码已存在!', $result[1]);
        $this->getSettingService()->delete('auth');
    }

    public function testCheckEmailOrMobileWithExistEmail()
    {
        $value = array('register_mode' => 'email_or_mobile');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'password' => '123456',
            'emailOrMobile' => 'test@edusoho.com',
            'nickname' => 'test',
        ));
        $result = $this->getAuthService()->checkEmailOrMobile('test@edusoho.com');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('Email已存在!', $result[1]);
        $this->getSettingService()->delete('auth');
    }

    /*
     * @group current
     */
    public function testCheckPasswordByTrue()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $result = $this->getAuthService()->checkPassword($user['id'], '123456');
        $this->assertTrue($result);
    }

    public function testChangePasswordByFalse()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '12456',
        ));

        $result = $this->getAuthService()->checkPassword($user['id'], '123456');
        $this->assertFalse($result);
    }

    public function testCheckPayPasswordByTrue()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));
        $this->getAuthService()->changePayPasswordWithoutLoginPassword($user['id'], '123456');
        $result = $this->getAuthService()->checkPayPassword($user['id'], '123456');
        $this->assertTrue($result);
    }

    public function testCheckPayPasswordByFalse()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));
        $this->getAuthService()->changePayPasswordWithoutLoginPassword($user['id'], '123456');
        $result = $this->getAuthService()->checkPayPassword($user['id'], '654321');
        $this->assertFalse($result);
    }

    /* 以下的带有partner的都需要访问Discuz等的API，默认default 返回false */
    public function testCheckPartnerLoginById()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $result = $this->getAuthService()->checkPartnerLoginById($user['id'], '123456');
        $this->assertFalse($result);
    }

    public function testCheckPartnerLoginByNickname()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $result = $this->getAuthService()->checkPartnerLoginByNickname($user['id'], 'test');
        $this->assertFalse($result);
    }

    public function testCheckPartnerLoginByEmail()
    {
        $value = array('register_mode' => 'default');
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register(array(
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ));

        $result = $this->getAuthService()->checkPartnerLoginByEmail($user['id'], 'test@edusoho.com');
        $this->assertFalse($result);
    }

    public function testIsRegisterEnabledWithOtherTypeByTrue()
    {
        $value = array('register_mode' => 'email_or_mobile');
        $this->getSettingService()->set('auth', $value);
        $result = $this->getAuthService()->isRegisterEnabled();
        $this->assertTrue($result);
        $this->getSettingService()->delete('auth');
    }

    public function testIsRegisterEnabledWithOtherTypeByFalse()
    {
        $value = array('register_mode' => 'testNotTrue');
        $this->getSettingService()->set('auth', $value);
        $result = $this->getAuthService()->isRegisterEnabled();
        $this->assertFalse($result);
        $this->getSettingService()->delete('auth');
    }

    public function testIsRegisterEnabledWithDefaultType()
    {
        $this->getSettingService()->delete('auth');
        $result = $this->getAuthService()->isRegisterEnabled();
        $this->assertTrue($result);
    }

    protected function getAuthService()
    {
        return $this->createService('User:AuthService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }
}
