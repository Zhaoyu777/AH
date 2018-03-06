<?php

namespace CustomBundle\Controller\Admin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\Admin\UserController as BaseController;
use Symfony\Component\HttpFoundation\Request;

class UserController extends BaseController
{
    protected function getRegisterData($formData, $clientIp)
    {
        if (isset($formData['email'])) {
            $userData['email'] = $formData['email'];
        }

        if (isset($formData['emailOrMobile'])) {
            $userData['emailOrMobile'] = $formData['emailOrMobile'];
        }

        if (isset($formData['mobile'])) {
            $userData['mobile'] = $formData['mobile'];
        }

        if (isset($formData['truename'])) {
            $userData['truename'] = $formData['truename'];
        }

        $userData['nickname'] = $formData['nickname'];
        $userData['password'] = $formData['password'];
        $userData['createdIp'] = $clientIp;
        $userData['type'] = $formData['type'];

        if (isset($formData['orgCode'])) {
            $userData['orgCode'] = $formData['orgCode'];
        }

        return $userData;
    }
}
