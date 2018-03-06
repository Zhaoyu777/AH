<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class AppController extends BaseController
{
    public function indexAction()
    {
    }

    public function oldUpgradeCheckAction()
    {
        return $this->redirect($this->generateUrl('admin_app_upgrades'));
    }

    public function centerAction(Request $request, $postStatus)
    {
        $apps = $this->getAppService()->getCenterApps();

        if (isset($apps['error'])) {
            return $this->render('admin/app/center.html.twig', array('status' => 'error', 'type' => $postStatus));
        }

        if (!$apps) {
            return $this->render('admin/app/center.html.twig', array('status' => 'unlink', 'type' => $postStatus));
        }

        $theme = array();
        $app = array();

        foreach ($apps as $key => $value) {
            $value['code'] = strtolower($value['code']);
            $apps[$key]['code'] = $value['code'];

            if ($value['type'] == 'theme') {
                $theme[] = $value;
            } elseif ($value['type'] == 'app') {
                $app[] = $value;
            }
        }

        $installedApps = $this->getAppService()->findApps(0, 100);
        $installedApps = ArrayToolkit::index($installedApps, 'code');

        foreach ($installedApps as $key => $value) {
            unset($installedApps[$key]);

            $key = strtolower($key);

            $installedApps[$key] = $value;
        }

        $showType = $request->query->get('showType');

        return $this->render('admin/app/center.html.twig', array(
            'apps' => $apps,
            'theme' => $theme,
            'allApp' => $app,
            'installedApps' => $installedApps,
            'type' => $postStatus,
            'appTypeChoices' => ($showType == 'hidden') ? 'installedApps' : null,
        ));
    }

    public function installedAction(Request $request, $postStatus)
    {
        $apps = $this->getAppService()->getCenterApps() ?: array();

        $apps = ArrayToolkit::index($apps, 'code');

        $appsInstalled = $this->getAppService()->findApps(0, 100);
        $appsInstalled = ArrayToolkit::index($appsInstalled, 'code');

        $dir = dirname(dirname(dirname(dirname(__DIR__))));
        $appMeta = array();

        foreach ($apps as $key => $value) {
            unset($apps[$key]);

            $appInfo = $value;
            $code = strtolower($key);

            $apps[$code] = $appInfo;
        }

        foreach ($appsInstalled as $key => $value) {
            $appItem = $key;
            unset($appsInstalled[$key]);

            $appInfo = $value;
            $key = strtolower($key);

            $appsInstalled[$key] = $appInfo;
            $appsInstalled[$key]['installed'] = 1;
            $appsInstalled[$key]['icon'] = !empty($apps[$key]['icon']) ? $apps[$key]['icon'] : null;

            if ($key != 'MAIN') {
                if (in_array($key, array('vip', 'coupon'))) {
                    $key = ucfirst($appItem);
                } else {
                    $key = $appItem;
                }

                $dic = $dir.'/plugins/'.$key.'/plugin.json';

                if (file_exists($dic)) {
                    $appMeta[$appItem] = json_decode(file_get_contents($dic));
                }
            }
        }

        $apps = array_merge($apps, $appsInstalled);

        $theme = array();
        $plugin = array();

        foreach ($apps as $key => $value) {
            if ($value['type'] == 'theme') {
                $theme[] = $value;
            } elseif ($value['type'] == 'plugin' || $value['type'] == 'app') {
                $plugin[] = $value;
            }
        }

        return $this->render('admin/app/installed.html.twig', array(
            'apps' => $apps,
            'theme' => $theme,
            'plugin' => $plugin,
            'type' => $postStatus,
            'appMeta' => $appMeta,
        ));
    }

    public function uninstallAction(Request $request)
    {
        $code = $request->get('code');
        $this->getAppService()->uninstallApp($code);

        return $this->createJsonResponse(true);
    }

    public function upgradesAction()
    {
        $apps = $this->getAppService()->checkAppUpgrades();

        if (isset($apps['error'])) {
            return $this->render('admin/app/upgrades.html.twig', array('status' => 'error'));
        }

        $version = $this->getAppService()->getMainVersion();

        return $this->render('admin/app/upgrades.html.twig', array(
            'apps' => $apps,
            'version' => $version,
        ));
    }

    public function upgradesCountAction()
    {
        $apps = $this->getAppService()->checkAppUpgrades();

        return $this->createJsonResponse(count($apps));
    }

    public function logsAction()
    {
        $paginator = new Paginator(
            $this->get('request'),
            $this->getAppService()->findLogCount(),
            30
        );

        $logs = $this->getAppService()->findLogs(
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($logs, 'userId'));

        return $this->render('admin/app/logs.html.twig', array(
            'logs' => $logs,
            'users' => $users,
            'paginator' => $paginator,
        ));
    }

    protected function getAppService()
    {
        return $this->createService('CloudPlatform:AppService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
