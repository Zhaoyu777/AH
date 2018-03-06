<?php

namespace Biz\Sms\SmsProcessor;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\SmsToolkit;
use AppBundle\Common\StringToolkit;
use Biz\Classroom\Service\ClassroomService;
use Biz\CloudPlatform\CloudAPIFactory;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\MemberService;
use Biz\System\Service\LogService;
use Biz\System\Service\SettingService;
use Biz\Task\Service\TaskService;
use Biz\User\Service\UserService;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class TaskSmsProcessor extends BaseSmsProcessor
{
    const PROCESSOR_TYPE = 'task';

    public function getUrls($targetId, $smsType)
    {
        $task = $this->getTaskService()->getTask($targetId);
        $course = $this->getCourseService()->getCourse($task['courseId']);
        $count = 0;

        if ($course['parentId'] != 0) {
            $classroom = $this->getClassroomService()->getClassroomByCourseId($course['id']);

            if ($classroom) {
                $count = $this->getClassroomService()->searchMemberCount(array(
                    'classroomId' => $classroom['id'],
                    'role' => 'student',
                ));
            }
        } else {
            $count = $this->getCourseMemberService()->countMembers(array('courseId' => $course['id'], 'role' => 'student'));
        }

        $api = CloudAPIFactory::create('root');

        global $kernel;
        $router = $kernel->getContainer()->get('router');
        $site = $this->getSettingService()->get('site');
        $url = empty($site['url']) ? $site['url'] : rtrim($site['url'], ' \/');

        $urls = array();
        for ($i = 0; $i <= (int) ($count / 1000); ++$i) {
            if (empty($url)) {
                $urls[$i] = $router->generate(
                    'edu_cloud_sms_send_callback',
                    array(
                        'targetType' => self::PROCESSOR_TYPE,
                        'targetId' => $task['id'],
                    ),
                    true
                );
            } else {
                $urls[$i] = $url.$router->generate('edu_cloud_sms_send_callback',
                        array('targetType' => self::PROCESSOR_TYPE, 'targetId' => $targetId));
            }
            $urls[$i] .= '?index='.($i * 1000);
            $urls[$i] .= '&smsType='.$smsType;
            $sign = $this->getSignEncoder()->encodePassword($urls[$i], $api->getAccessKey());
            $sign = rawurlencode($sign);
            $urls[$i] .= '&sign='.$sign;
        }

        return array('count' => $count, 'urls' => $urls);
    }

    public function getSmsInfo($targetId, $index, $smsType)
    {
        $task = $this->getTaskService()->getTask($targetId);
        if (empty($task)) {
            throw new NotFoundException('学习任务不存在');
        }

        global $kernel;
        $site = $this->getSettingService()->get('site');
        $url = empty($site['url']) ? $site['url'] : rtrim($site['url'], ' \/');

        if (empty($url)) {
            $originUrl = $kernel->getContainer()->get('router')->generate('course_task_show',
                array('courseId' => $task['courseId'], 'id' => $task['id']), true);
        } else {
            $originUrl = $url.$kernel->getContainer()->get('router')->generate('course_task_show',
                    array('courseId' => $task['courseId'], 'id' => $task['id']));
        }

        $shortUrl = SmsToolkit::getShortLink($originUrl);
        $url = empty($shortUrl) ? $originUrl : $shortUrl;

        $courseSet = $this->getCourseSetService()->getCourseSet($task['fromCourseSetId']);

        $students = array();
        if ($courseSet['parentId']) {
            $classroom = $this->getClassroomService()->getClassroomByCourseId($task['courseId']);

            if ($classroom) {
                $students = $this->getClassroomService()->searchMembers(array('classroomId' => $classroom['id'], 'role' => 'student'),
                    array('createdTime' => 'Desc'), $index, 1000);
            }
        } else {
            $students = $this->getCourseMemberService()->searchMembers(array('courseId' => $task['courseId'], 'role' => 'student'),
                array('createdTime' => 'Desc'), $index, 1000);
        }

        $studentIds = ArrayToolkit::column($students, 'userId');
        $to = $this->getUsersMobile($studentIds);

        $task['title'] = StringToolkit::cutter($task['title'], 20, 15, 4);
        $parameters['lesson_title'] = '学习任务：《'.$task['title'].'》';

        if ($task['type'] == 'live') {
            $parameters['startTime'] = date('Y-m-d H:i:s', $task['startTime']);
        }

        $courseSet['title'] = StringToolkit::cutter($courseSet['title'], 20, 15, 4);
        $parameters['course_title'] = '课程：《'.$courseSet['title'].'》';

        if ($smsType == 'sms_normal_lesson_publish' || $smsType == 'sms_live_lesson_publish') {
            $description = $parameters['course_title'].' '.$parameters['lesson_title'].'已发布';
        } else {
            $description = $parameters['course_title'].' '.$parameters['lesson_title'].'预告';
        }

        $parameters['url'] = $url.' ';

        $this->getLogService()->info('sms', $smsType, $description, array($to));

        return array(
            'mobile' => $to,
            'category' => $smsType,
            'sendStyle' => 'templateId',
            'description' => $description,
            'parameters' => $parameters,
        );
    }

    /**
     * @param $userIds
     *
     * @return string
     */
    protected function getUsersMobile($userIds)
    {
        $mobiles = $this->getUserService()->findUnlockedUserMobilesByUserIds($userIds);
        $to = implode(',', $mobiles);

        return $to;
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getBiz()->service('User:UserService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->getBiz()->service('System:LogService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->getBiz()->service('Course:CourseSetService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->getBiz()->service('Classroom:ClassroomService');
    }

    protected function getSignEncoder()
    {
        return new MessageDigestPasswordEncoder('sha256');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->getBiz()->service('Task:TaskService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->getBiz()->service('Course:MemberService');
    }
}
