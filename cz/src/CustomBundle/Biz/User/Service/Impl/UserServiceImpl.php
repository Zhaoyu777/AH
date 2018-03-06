<?php

namespace CustomBundle\Biz\User\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\User\Service\UserService;
use Codeages\Biz\Framework\Event\Event;
use AppBundle\Common\SimpleValidator;
use CustomBundle\Common\BeanstalkClient;
use Biz\User\Service\Impl\UserServiceImpl as BaseUserServiceImpl;

class UserServiceImpl extends BaseUserServiceImpl implements UserService
{
    public function getUser($id, $lock = false)
    {
        $user = parent::getUser($id, $lock);

        return $this->tranNickname2Truaname($user);
    }

    public function searchUsers(array $conditions, array $orderBy, $start, $limit)
    {
        $users = parent::searchUsers($conditions, $orderBy, $start, $limit);

        if (!empty($users)) {
            $users = $this->tranNicknames2Truanames($users);
        }

        return $users;
    }

    public function countAllUsers($conditions)
    {
        return $this->getUserDao()->countAllUsers($conditions);
    }

    public function searchAllUsers(array $conditions, array $orderBy, $start, $limit)
    {
        $users = $this->getUserDao()->searchAllUsers($conditions, $orderBy, $start, $limit);
        if (!empty($users)) {
            $users = $this->tranNicknames2Truanames($users);
        }

        return $users;
    }

    public function getSimpleUser($id)
    {
        $user = parent::getSimpleUser($id);

        return $this->tranNickname2Truaname($user);
    }

    public function getUserByNickname($nickname)
    {
        $fields = explode('-', $nickname);
        if (isset($fields[1])) {
            $nickname = $fields[1];
        }
        $user = parent::getUserByNickname($nickname);

        return $this->tranNickname2Truaname($user);
    }

    public function getUserByLoginField($keyword)
    {
        if (SimpleValidator::email($keyword)) {
            $user = $this->getUserDao()->getByEmail($keyword);
        } elseif (SimpleValidator::mobile($keyword)) {
            $user = $this->getUserDao()->getByVerifiedMobile($keyword);
            if (empty($user)) {
                $user = $this->getUserDao()->getByNickname($keyword);
            }
        } else {
            $user = $this->getUserDao()->getByNickname($keyword);
        }

        if (isset($user['type']) && $user['type'] == 'system') {
            return null;
        }

        $user = !$user ? null : UserSerialize::unserialize($user);

        return $this->tranNickname2Truaname($user);
    }

    public function getUserByVerifiedMobile($mobile)
    {
        $user = parent::getUserByVerifiedMobile($mobile);

        return $this->tranNickname2Truaname($user);
    }

    public function getUserByEmail($email)
    {
        $user = parent::getUserByEmail($email);

        return $this->tranNickname2Truaname($user);
    }

    public function findUsersByIds(array $ids)
    {

        $users = parent::findUsersByIds($ids);

        if (!empty($users)) {
            $users = $this->tranNicknames2Truanames($users);
        }

        return $users;
    }

    public function changeEmail($userId, $email)
    {
        $user = parent::changeEmail($userId, $email);

        return $this->tranNickname2Truaname($user);
    }

    public function changePassword($id, $password)
    {
        parent::changePassword($id, $password);
        return $this->getUserDao()->update($id, array('passwordChange' => 1));
    }

    public function changeAvatar($userId, $data)
    {
        $user = parent::changeAvatar($userId, $data);

        return $this->tranNickname2Truaname($user);
    }

    public function updateUserUpdatedTime($id)
    {
        $user = parent::updateUserUpdatedTime($id);

        return $this->tranNickname2Truaname($user);
    }

    public function getUserByType($type)
    {
        $user = parent::getUserByType($type);

        return $this->tranNickname2Truaname($user);
    }

    public function register($registration, $type = 'default')
    {
        $user = parent::register($registration, $type = 'default');
        if ($type == 'import' || (isset($registration['type']) && $registration['type'] == 'import')) {
            $this->getUserDao()->update($user['id'], array('passwordChange' => 0));
        }

        if (!empty($registration['truename'])) {
            $this->updateUserProfile($user['id'], array('truename' => $registration['truename']));
        }

        $this->getSignInService()->initWarning($user['id']);

        return $this->tranNickname2Truaname($user);
    }

    public function promoteUser($id, $number)
    {
        $user = parent::promoteUser($id, $number);

        return $this->tranNickname2Truaname($user);
    }

    public function cancelPromoteUser($id)
    {
        $user = parent::cancelPromoteUser($id);

        return $this->tranNickname2Truaname($user);
    }

    public function updateUserLocale($id, $locale)
    {
        $user = parent::updateUserLocale($id, $locale);

        return $this->tranNickname2Truaname($user);
    }

    public function markWeixinLoginInfo()
    {
        $user = $this->getCurrentUser();

        if (empty($user)) {
            return;
        }

        if ($this->isOpenWorker()) {
            BeanstalkClient::putTubeMessage('MarkUserLoginInfoWorker', array(
                'userId' => $user['id'],
                'loginIp' => $user['currentIp'],
                'loginTime' => time(),
            ));
        } else {
            $this->markWeixinLoginInfoProcess($user['id'], array(
                'loginIp' => $user['currentIp'],
                'loginTime' => time(),
            ));
        }
    }

    public function markWeixinLoginInfoProcess($userId, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array(
            'loginIp',
            'loginTime',
        ));
        $this->getUserDao()->update($userId, $fields);

        $this->getLogService()->info('user', 'login_success', '登录成功');
    }

    public function countTeachersByCode($code)
    {
        return $this->getUserDao()->countTeachersByCode($code);
    }

    protected function tranNickname2Truaname($user)
    {
        if (!empty($user)) {
            $profile = $this->getUserProfile($user['id']);
            $user['number'] = $user['nickname'];
            $user['truename'] = empty($profile['truename']) ? $user['nickname'] : $profile['truename'];
            $user['nickname'] = empty($profile['truename']) ? $user['nickname'] : "{$profile['truename']}-{$user['nickname']}";
        }

        return $user;
    }

    protected function tranNicknames2Truanames($users)
    {
        if (!empty($users)) {
            $userIds = ArrayToolkit::column($users, 'id');
            $profiles = $this->findUserProfilesByIds($userIds);

            array_walk(
                $users,
                function (&$user) use ($profiles) {
                    $user['number'] = $user['nickname'];
                    $user['truename'] = empty($profiles[$user['id']]['truename']) ? $user['nickname'] : $profiles[$user['id']]['truename'];
                    $user['nickname'] = empty($profiles[$user['id']]['truename']) ? $user['nickname'] : "{$profiles[$user['id']]['truename']}-{$user['nickname']}";
                }
            );
        }

        return $users;
    }

    protected function isOpenWorker()
    {
        $magic = $this->createService('System:SettingService')->get('magic');

        if (isset($magic['open_worker']) && $magic['open_worker']) {
            return true;
        }

        return false;
    }

    public function searchAnalysisTeachers($conditions, $start, $limit)
    {
        return $this->getTeacherDao()->searchAnalysisTeachers($conditions, $start, $limit);
    }

    public function countAnalysisTeachers($conditions)
    {
        return $this->getTeacherDao()->countAnalysisTeachers($conditions);
    }

    protected function getUserDao()
    {
        return $this->createDao('CustomBundle:User:UserDao');
    }

    protected function getTeacherDao()
    {
        return $this->createDao('CustomBundle:User:TeacherDao');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }
}

class UserSerialize
{
    public static function serialize(array $user)
    {
        return $user;
    }

    public static function unserialize(array $user = null)
    {
        if (empty($user)) {
            return null;
        }

        $user = self::_userRolesSort($user);

        return $user;
    }

    public static function unserializes(array $users)
    {
        return array_map(function ($user) {
            return UserSerialize::unserialize($user);
        }, $users);
    }

    private static function _userRolesSort($user)
    {
        if (!empty($user['roles'][1]) && $user['roles'][1] == 'ROLE_USER') {
            $temp = $user['roles'][1];
            $user['roles'][1] = $user['roles'][0];
            $user['roles'][0] = $temp;
        }

        //交换学员角色跟roles数组第0个的位置;

        return $user;
    }
}

