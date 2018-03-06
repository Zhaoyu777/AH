<?php
namespace AppBundle\Command;

use Biz\User\CurrentUser;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CzieSyncDataCommand extends BaseCommand
{
    protected $logger;
    protected function configure()
    {
        $this->setName('czie:sync-data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        putenv('IS_RUN_BY_COMMAND=true');
        $biz                             = $this->getBiz();
        $biz['dao.cache.first.enabled']  = false;
        $biz['dao.cache.second.enabled'] = false;
        $this->initServiceKernel();

        // $this->getSyncDataService()->syncTerms();

        // $this->getSyncDataService()->syscCourseSet();

        // $this->getSyncDataService()->syscStudents();

        // $this->getSyncDataService()->syscTeacherOrgs();

        // $this->getSyncDataService()->syscTeachers();

        // $this->getSyncDataService()->syscCourses();

        // $this->getSyncDataService()->syncAll();
        $this->getSyncDataService()->saveApiData();
    }

    protected function initServiceKernel()
    {
        $serviceKernel = ServiceKernel::create('dev', true);
        $currentUser   = new CurrentUser();
        $currentUser->fromArray(array(
            'id'        => 1,
            'nickname'  => '超级管理员',
            'currentIp' => '127.0.0.1',
            'roles'     => array('ROLE_SUPER_ADMIN'),
            'orgId'     => 1
        ));
        $serviceKernel->setCurrentUser($currentUser);
    }

    protected function getSyncDataService()
    {
        return $this->getContainer()->get('biz')->service('CustomBundle:Api:CzieSyncDataService');
    }
}
