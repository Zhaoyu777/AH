<?php

namespace Biz\User;

use Symfony\Component\Security\Core\User\UserInterface;

class AnonymousUser extends CurrentUser
{
    public function __construct($ip)
    {
        $this->data = array(
            'id' => 0,
            'nickname' => '游客',
            'email' => 'test.edusoho.com',
            'currentIp' => $ip,
            'roles' => array(),
            'locked' => false,
            'org' => array('id' => $this->rootOrgId, 'orgCode' => $this->rootOrgCode),
            'orgId' => $this->rootOrgId,
            'orgCode' => $this->rootOrgCode,
        );
    }

    public function serialize()
    {
        return parent::serialize();
    }

    public function unserialize($serialized)
    {
        parent::unserialize($serialized);
    }

    public function __set($name, $value)
    {
        return parent::__set($name, $value);
    }

    public function __get($name)
    {
        $method = 'get'.ucfirst($name);

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return parent::__get($name);
    }

    public function __isset($name)
    {
        return parent::__isset($name);
    }

    public function __unset($name)
    {
        parent::__unset($name);
    }

    public function clearNotifacationNum()
    {
        parent::clearNotifacationNum();
    }

    public function clearMessageNum()
    {
        parent::clearMessageNum();
    }

    public function offsetExists($offset)
    {
        return parent::offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return parent::offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        return parent::offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return parent::offsetUnset($offset);
    }

    public function getRoles()
    {
        return array();
    }

    public function getPassword()
    {
        return '';
    }

    public function getSalt()
    {
        return '';
    }

    public function getUsername()
    {
        return '游客';
    }

    public function getId()
    {
        return 0;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return true;
    }

    public function getLocale()
    {
        return parent::getLocale();
    }

    public function isEqualTo(UserInterface $user)
    {
        return parent::isEqualTo($user);
    }

    public function isLogin()
    {
        return false;
    }

    public function isAdmin()
    {
        return false;
    }

    public function isSuperAdmin()
    {
        return false;
    }

    public function isTeacher()
    {
        return false;
    }

    public function getCurrentOrgId()
    {
        return 0;
    }

    public function getCurrentOrg()
    {
        return array();
    }

    public function getSelectOrg()
    {
        return array();
    }

    public function getOrg()
    {
        return array('id' => $this->rootOrgId, 'orgCode' => $this->rootOrgCode);
    }

    public function getOrgCode()
    {
        return $this->rootOrgCode;
    }

    public function getOrgId()
    {
        return $this->rootOrgId;
    }

    public function getSelectOrgCode()
    {
        return $this->rootOrgCode;
    }

    public function getSelectOrgId()
    {
        return $this->rootOrgId;
    }

    public function fromArray(array $user)
    {
        return $this;
    }

    public function toArray()
    {
        return $this->data;
    }

    public function setPermissions($permissions)
    {
        return $this;
    }

    public function getPermissions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission($code)
    {
        return false;
    }
}
