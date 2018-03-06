<?php

namespace CustomBundle;

use Codeages\PluginBundle\System\PluginBase;

class CustomBundle extends PluginBase
{
    // public function getParent()
    // {
    //     return 'AppBundle';
    // }

    public function boot()
    {
        parent::boot();
        $serviceAlias = $this->getRewriteServiceAlias();
        $daoAlias = $this->getRewriteDaoAlias();
        $this->rewriteService($serviceAlias);
        $this->rewriteDao($daoAlias);
        $this->registerProviders();
    }

    protected function getRegisterProviders()
    {
        return array(
            'CustomBundle\Biz\DefaultServiceProvider',
        );
    }

    public function getRewriteServiceAlias()
    {
        return array(
            'Activity:ActivityService',
            'Course:CourseSetService',
            'User:UserService',
            'Course:CourseService',
        );
    }

    public function getRewriteDaoAlias()
    {
        return array(
            'Course:CourseMemberDao',
            'Course:CourseSetDao',
        );
    }

    public function rewriteService($serviceAliases)
    {
        $biz = $this->container->get('biz');
        //rewrite service
        foreach ($serviceAliases as $serviceAlias) {
            $biz["@{$serviceAlias}"] = $biz->service("CustomBundle:{$serviceAlias}");
        }
    }

    public function rewriteDao($daoAliases)
    {
        $biz = $this->container->get('biz');
        //rewrite service
        foreach ($daoAliases as $daoAlias) {
            $biz["@{$daoAlias}"] = $biz->dao("CustomBundle:{$daoAlias}");
        }
    }

    public function getEnabledExtensions()
    {
        return array('DataTag', 'StatusTemplate', 'DataDict', 'NotificationTemplate');
    }

    protected function registerProviders()
    {
        $biz = $this->container->get('biz');
        $providers = $this->getRegisterProviders();

        foreach ($providers as $key => $provider) {
            $biz->register(new $provider());
        }
    }
}
