<?php

namespace CustomBundle\Biz\SignIn\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\CurlToolkit;
use CustomBundle\Common\WeixinClient;
use CustomBundle\Common\Platform\PlatformFactory;

class SignInWarningJob extends AbstractJob
{
    private $appid = '58171047371434360415';

    private $perMsgCount = 20;

    private $appkey = 'e885c1736feaa486eeb9aac4a6cd73a1';

    private $tokenUrl = 'http://172.18.0.64:8082/ly_web_services/validation';

    private $bhUrl = 'http://172.18.0.64:8082/ly_web_services/web/XSJBXX_XH';

    private $bhBzrUrl = 'http://172.18.0.64:8082/ly_web_services/web/BH_BZR';

    public function execute()
    {
        $params = $this->args;
        try {
            $setting = $this->getSettingService()->get('warning', array());
            $accessToken = $this->getAccessToken();

            if (!empty($setting['signInTeacherWarning'])) {
                $this->sendWarnings($setting['signInTeacherWarning'], $accessToken, 'masterTeacher');
            }

            if (!empty($setting['signInLeaderWarning'])) {
                $this->sendWarnings($setting['signInLeaderWarning'], $accessToken, 'leader');
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @ String $type 通知人类型，masterTeacher 为发给班主任,leader为院系领导
     */
    private function sendWarnings($count, $accessToken, $type)
    {
        $warningList = $this->getSignInService()->findWarningList($count);

        $userIds = ArrayToolkit::column($warningList, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $numbers = ArrayToolkit::column($users, 'number');
        $classRooms = array();

        foreach ($numbers as $number) {
            $data = CurlToolkit::request(
                'GET',
                $this->bhUrl,
                array('appid' => $this->appid, 'access_token' => $accessToken, 'XH' => $number),
                array('userAgent' => 'warning', 'contentType' => 'plain')
            );

            $data = reset(json_decode($data)->data);

            if (empty($data)) {
                continue;
            }
            $classRooms[] = json_decode(json_encode($data), true);
        }
        $classRooms = ArrayToolkit::group($classRooms, 'bh');

        $masterTeachers = array();
        $teacherNames = array();
        $leaders = array();
        foreach ($classRooms as $bh => $classRoom) {
            $data = CurlToolkit::request(
                'GET',
                $this->bhBzrUrl,
                array('appid' => $this->appid, 'access_token' => $accessToken, 'BH_trim' => $bh),
                array('userAgent' => 'edusoho', 'contentType' => 'plain')
            );

            $data = reset(json_decode($data)->data);
            $data = json_decode(json_encode($data), true);

            $teacherNames[$data['bzrgh']] = $data['bzrxm'];

            if ($type == 'masterTeacher') {
                $masterTeachers[$data['bzrgh']] = $classRoom;
            }

            if ($type == 'leader') {
                $masterTeacher = $this->getUserService()->getUserByNickname($data['bzrgh']);
                $leaders[$masterTeacher['orgId']][$data['bzrgh']] = $classRoom;
            }
        }

        $weixinClient = $this->getPlatformClient();
        if ($type == 'leader') {
            foreach ($leaders as $orgId => $masterTeachers) {
                $facultyLeaders = $this->getOrgService()->findFacultyLeadersByOrgId($orgId);
                if (empty($facultyLeaders)) {
                    continue;
                }
                $leaderUserIds = ArrayToolkit::column($facultyLeaders, 'userId');
                $facultyLeaders = $this->getUserService()->findUsersByIds($leaderUserIds);
                $leaderNumbers = ArrayToolkit::column($facultyLeaders, 'number');
                foreach ($masterTeachers as $teacherNumber => $students) {
                    $messages = array();
                    $message = "今日缺勤预警（连续缺勤{$count}次）：";
                    $flag = 1;
                    foreach ($students as $student) {
                        $message .= "\n\n{$flag}.{$student['xm']}-{$student['xh']}\n班级：{$student['bj']}\n班主任：{$teacherNames[$teacherNumber]}";
                        if ($flag % $this->perMsgCount == 0) {
                            $messages[] = $message;
                            $message = "今日缺勤预警（连续缺勤{$count}次）：";
                        }
                        $flag ++;
                    }

                    if ($flag % $this->perMsgCount != 1) {
                        $messages[] = $message;
                    }

                    foreach ($messages as $message) {
                        $weixinClient->sendTextMessage($leaderNumbers, $message);
                    }
                }
            }
        }

        if ($type == 'masterTeacher') {
            foreach ($masterTeachers as $teacherNumber => $students) {
                $messages = array();
                $message = "今日缺勤预警（连续缺勤{$count}次）：";
                $flag = 1;
                foreach ($students as $student) {
                    $message .= "\n\n{$flag}.{$student['xm']}-{$student['xh']}\n班级：{$student['bj']}\n班主任：{$teacherNames[$teacherNumber]}";
                    if ($flag % $this->perMsgCount == 0) {
                        $messages[] = $message;
                        $message = "今日缺勤预警（连续缺勤{$count}次）：";
                    }
                    $flag ++;
                }

                if ($flag % $this->perMsgCount != 1) {
                    $messages[] = $message;
                }

                foreach ($messages as $message) {
                    $weixinClient->sendTextMessage(array($teacherNumber), $message);
                    // $weixinClient->sendTextMessage(array('8000000902'), $message);
                }
            }
        }
    }

    protected function getAccessToken()
    {
        $data = CurlToolkit::request(
            'GET',
            $this->tokenUrl,
            array('appid' => $this->appid, 'appkey' => $this->appkey),
            array('userAgent' => 'edusoho')
        );

        return $data['access_token'];
    }

    protected function getPlatformClient()
    {
        $biz = $this->getServiceKernel()->getBiz();
        $weixinClient = PlatformFactory::create($biz);

        return $weixinClient;
    }

    // protected function getPlatformClient()
    // {
    //     $biz = $this->getServiceKernel()->getBiz();
    //     $weixinClient = WeixinClient::getInstance(array());

    //     $weixinClient->setBiz($biz);

    //     return $weixinClient;
    // }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User:UserService');
    }

    protected function getSignInService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getOrgService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Org:OrgService');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System:SettingService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
