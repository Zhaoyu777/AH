<?php

namespace CustomBundle\Biz\Lesson\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Common\Platform\PlatformFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class TeachingAimWarningJob extends AbstractJob
{
    private $limitRate = 0;

    private $limitTimes = 0;

    private $weixinClient;

    private $logger;

    public function execute()
    {
        $params = $this->args;
        $this->getLogger()->info("开始统计本周预警课程个数");
        $warningSettings = $this->getSettingService()->get('warning', array());
        if (empty($warningSettings['teachingAimWarningRate']) && empty($warningSettings['teachingAimWarningTimes'])) {
            return;
        }

        $this->limitRate = round((float)($warningSettings['teachingAimWarningRate'] / 100), 3);
        $this->limitTimes = $warningSettings['teachingAimWarningTimes'];

        list(
            $deleteCourseIds,
            $waveCourses,
            $createRecords,
        ) = $this->makeProcessDatas();

        $this->beginTransaction();
        try {
            $this->getTeachingAimWarningService()->batchCreate($createRecords);
            $waveCourseIds = ArrayToolkit::column($waveCourses, 'courseId');
            $this->getTeachingAimWarningService()->addWarningTimeByCourseIds($waveCourseIds);
            $this->getTeachingAimWarningService()->deleteByCourseIds($deleteCourseIds);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            $this->getLogger()->info("统计预警课程失败");
            throw $e;
        }

        $sendToTeacherCourseIds = array();
        $sendToManagerCourseIds = array();
        foreach ($waveCourses as $record) {
            if ($record['times'] >= $this->limitTimes) {
                $sendToManagerCourseIds[] = $record['courseId'];
            }

            $sendToTeacherCourseIds[] = $record['courseId'];
        }

        $this->getLogger()->info("本周预警课程有".(count($sendToTeacherCourseIds) + count($sendToManagerCourseIds))."个, 需要通知学院负责人的课程有".(count($sendToManagerCourseIds))."个");
        try {
            $this->sendToTeacher($sendToTeacherCourseIds);
            $this->sendToManager($sendToManagerCourseIds);
        } catch (\Exception $e) {
            $this->getLogger()->info("本周所有预警信息发送失败");
        }
    }

    protected function makeProcessDatas()
    {
        // 所有预警的课程
        $warningCourses = $this->getCourseStatisticsService()->findTeachingAimWarningCoursesByValue($this->limitRate);
        $warningCourseIds = ArrayToolkit::column($warningCourses, 'courseId');
        // 去掉没有教学目标的课程
        $warningCourses = $this->getTeachignAimService()->findUniqueCourseIds($warningCourseIds);
        $warningCourseIds = ArrayToolkit::column($warningCourses, 'courseId');

        // 预警表里面已经存在的历史预警课程
        $allWarningCourses = $this->getTeachingAimWarningService()->findAllWarningCourses();
        $allWarningCourseIds = ArrayToolkit::column($allWarningCourses, 'courseId');

        // 带着本周预警的课程的ids,去预警表里面查是否有该课程，没有的话，记录下diff的courseIds
        $warningRecords = $this->getTeachingAimWarningService()->findTeachingAimWarningCoursesByCourseIds($warningCourseIds);
        $warningRecords = ArrayToolkit::index($warningRecords, 'courseId');
        $deleteCourseIds = array_diff($allWarningCourseIds, $warningCourseIds);
        $deleteCourseIds = array_merge($deleteCourseIds);

        $createRecords = array();
        $waveCourses = array();
        foreach ($warningCourseIds as $courseId) {
            //如果在预警表里面不存在这个课程，说明是新的预警，则要插入表中，所以记录下该记录
            if (!isset($warningRecords[$courseId])) {
                $createRecords[] = array(
                    'courseId' => $courseId,
                    'times' => 1
                );
            } else {
                //如果在预警表里面存在这个课程，则预警次数需要+1，所以记录下该条记录
                $waveCourses[] = $warningRecords[$courseId];
            }
        }

        return array(
            $deleteCourseIds,
            $waveCourses,
            $createRecords,
        );
    }

    protected function sendToTeacher($courseIds)
    {
        if (empty($courseIds) || empty($this->limitTimes)) {
            return ;
        }

        // 根据courseIds，找出所有course所属的courseSet的信息
        $allCourses = $this->getCourseService()->findCoursesByIds($courseIds);
        $allCourses = ArrayToolkit::index($allCourses, 'id');
        $allCourseSetIds = ArrayToolkit::column($allCourses, 'courseSetId');
        $allCourseSets = $this->getCourseSetService()->findCourseSetsByIds($allCourseSetIds);
        $allCourseSets = ArrayToolkit::index($allCourseSets, 'id');

        //找到每个课程的主带老师的id
        $mainTeachers = $this->getMemberService()->findMainTeachersByCourseIds($courseIds);
        $mainTeachersIds = ArrayToolkit::column($mainTeachers, 'teacherId');
        // 获取每个主带老师的user信息
        $teachers = $this->getUserService()->findUsersByIds($mainTeachersIds);
        //课程按主带老师分组
        $mainTeachersCourses = ArrayToolkit::group($mainTeachers, 'courseId');

        $messages = array();
        $limitRate = $this->limitRate * 100;
        foreach ($mainTeachersCourses as $teacherCourses) {
            $tmpMessage = "本周教学目标达成度预警(低于{$limitRate}%)：\n";
            $number = 1;
            foreach ($teacherCourses as $key => $course) {
                if ($number % 2 == 0 || count($teacherCourses) == ($key + 1)) {
                    $tmpMessage .= "{$number}.{$allCourseSets[$allCourses[$course['courseId']]['courseSetId']]['title']}-{$allCourses[$course['courseId']]['title']}";

                    $this->sendMessage(array($teachers[$course['teacherId']]['number']), $tmpMessage);
                    $tmpMessage = '';
                } else {
                    $tmpMessage .= "{$number}.{$allCourseSets[$allCourses[$course['courseId']]['courseSetId']]['title']}-{$allCourses[$course['courseId']]['title']}\n\n";
                }

                ++$number;
            }
        }
    }

    protected function sendToManager($courseIds)
    {
        if (empty($courseIds) || empty($this->limitTimes)) {
            return ;
        }

        // 根据courseIds，找出所有course所属的courseSet的信息
        $allCourses = $this->getCourseService()->findCoursesByIds($courseIds);
        $allCourses = ArrayToolkit::index($allCourses, 'id');
        $allCourseSetIds = ArrayToolkit::column($allCourses, 'courseSetId');
        $allCourseSets = $this->getCourseSetService()->findCourseSetsByIds($allCourseSetIds);
        $allCourseSets = ArrayToolkit::index($allCourseSets, 'id');

        //找到每个课程的主带老师的id
        $mainTeachers = $this->getMemberService()->findMainTeachersByCourseIds($courseIds);

        // 获取每个主带老师的user信息
        $mainTeachersIds = ArrayToolkit::column($mainTeachers, 'teacherId');
        $teachers = $this->getUserService()->findUsersByIds($mainTeachersIds);

        //课程按主带老师分组
        $mainTeachersCourses = ArrayToolkit::group($mainTeachers, 'teacherId');

        $messages = array();
        foreach ($mainTeachersCourses as $teacherCourses) {
            foreach ($teacherCourses as $key => $course) {
                $tmpMessage = '';
                $tmpMessage .= "{$allCourseSets[$allCourses[$course['courseId']]['courseSetId']]['title']}-{$allCourses[$course['courseId']]['title']}\n";
                $tmpMessage .= "主带老师:{$teachers[$course['teacherId']]['nickname']}-{$teachers[$course['teacherId']]['number']}\n";
                $messages[] = array(
                    'message' => $tmpMessage,
                    'orgId' => $teachers[$course['teacherId']]['orgId'],
                );
            }
        }

        $leadersmessages = ArrayToolkit::group($messages, 'orgId');
        $limitRate = $this->limitRate * 100;
        foreach ($leadersmessages as $leadermessages) {
            $text = "本周教学目标达成度预警(连续{$this->limitTimes}次低于{$limitRate}%)：\n";
            $number = 1;
            foreach ($leadermessages as $key => $message) {
                if ($number % 2 == 0 || count($leadermessages) == ($key + 1)) {
                    $text .= "{$number}.".$message['message'];
                    $this->sendMessage(
                        $this->makeCollegeFacultiesNumbers($message['orgId']),
                        $text
                    );
                    $text = '';
                } else {
                    $text .= "{$number}.".$message['message']."\n";
                }
                ++$number;
            }
        }
    }

    protected function makeCollegeFacultiesNumbers($orgId)
    {
        $leaders = $this->getOrgService()->findFacultyLeadersByOrgId($orgId);

        if (empty($leaders)) {
            return array();
        }

        $leadersUserIds = ArrayToolkit::column($leaders, 'userId');
        $leadersUsers = $this->getUserService()->findUsersByIds($leadersUserIds);

        return ArrayToolkit::column($leadersUsers, 'number');
    }

    protected function sendMessage($numbers, $message)
    {
        if (empty($numbers)) {
            return ;
        }

        $weixinClient = $this->getPlatformClient();

        $weixinClient->sendTextMessage($numbers, $message);
    }

    protected function getPlatformClient()
    {
        if (empty($this->weixinClient)) {
            $biz = $this->getServiceKernel()->getBiz();

            $this->weixinClient = PlatformFactory::create($biz);
        }

        return $this->weixinClient;
    }

    protected function getTeachignAimService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Lesson:TeachingAimService');
    }

    protected function getCourseSetService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getOrgService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Org:OrgService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:User:UserService');
    }

    protected function getMemberService()
    {
        return $this->getServiceKernel()->createService("CustomBundle:Course:MemberService");
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService("CustomBundle:Course:CourseService");
    }

    protected function getTeachingAimWarningService()
    {
        return $this->getServiceKernel()->createService("CustomBundle:Lesson:TeachingAimWarningService");
    }

    protected function getCourseStatisticsService()
    {
        return $this->getServiceKernel()->createService("CustomBundle:Statistics:CourseStatisticsService");
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System:SettingService');
    }

    protected function beginTransaction()
    {
        $biz = $this->getBiz();
        $biz['db']->beginTransaction();
    }

    protected function commit()
    {
        $biz = $this->getBiz();
        $biz['db']->commit();
    }

    protected function rollback()
    {
        $biz = $this->getBiz();
        $biz['db']->rollback();
    }

    protected function getLogger($name = 'zhkt-teaching-aim-warning')
    {
        if ($this->logger) {
            return $this->logger;
        }

        $this->logger = new Logger($name);

        $biz = $this->getBiz();
        $this->logger->pushHandler(new StreamHandler($biz['log_directory'].'/job.log', Logger::DEBUG));

        return $this->logger;
    }

    protected function getBiz()
    {
        return $this->getServiceKernel()->getBiz();
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
