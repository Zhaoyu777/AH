<?php

namespace Biz\User;

use Biz\User\Service\UserService;
use Biz\Role\Util\PermissionBuilder;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Handler\AuthenticationHelper;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->getUserService()->getUserByLoginField($username);

        if (empty($user)) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        } elseif (isset($user['type']) && $user['type'] == 'system') {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        $request = $this->container->get('request');

        $forbidden = AuthenticationHelper::checkLoginForbidden($request);
        if ($forbidden['status'] == 'error') {
            throw new AuthenticationException($forbidden['message']);
        }

        $user['currentIp'] = $request->getClientIp();
        $user['org'] = $this->loadOrg($request, $user);
        $currentUser = new CurrentUser();
        $currentUser->fromArray($user);
        $currentUser->setPermissions(PermissionBuilder::instance()->getPermissionsByRoles($currentUser->getRoles()));
        $biz = $this->container->get('biz');
        $biz['user'] = $currentUser;
        ServiceKernel::instance()->setCurrentUser($currentUser);

        return $currentUser;
    }

    protected function loadOrg($request, $user)
    {
        $org = $request->getSession()->get('currentUserOrg', array());
        if (empty($org) || $org['orgCode'] != $user['orgCode']) {
            $org = $this->getOrgService()->getOrgByOrgCode($user['orgCode']);
            $request->getSession()->set('currentUserOrg', $org);
        }

        return $org;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof CurrentUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Biz\User\CurrentUser';
    }

    protected function getRoleService()
    {
        return ServiceKernel::instance()->createService('Role:RoleService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->container->get('biz')->service('User:UserService');
    }

    protected function getOrgService()
    {
        return ServiceKernel::instance()->createService('Org:OrgService');
    }
}
