<?php

namespace CustomBundle\Biz\Course\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Common\Platform\PlatformFactory;

class CourseTaskCompleWarningjob extends AbstractJob
{
    public function execute()
    {
        $params = $this->args;
        try {
            $settingMobile = $this->getSettingService()->get('warning', array());
            $termCode = $this->getCourseService()->getCurrentTerm();
            $warnValue = $settingMobile['taskInCompleWarning'];
            if ($warnValue === 0) {
                return ;
            }

            $courseStatistics = $this->getCourseStatisticsService()->findCoursesInCompleByTermCodeAndWarnValue($termCode['shortCode'], $warnValue / 100);

            $courseIds = ArrayToolkit::column($courseStatistics, 'courseId');
            $courses = $this->getCourseService()->findCoursesByIds($courseIds);
            $courses = ArrayToolkit::index($courses, 'id');

            $courseSetIds =  ArrayToolkit::column($courses, 'courseSetId');
            $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);
            $courseSets = ArrayToolkit::index($courseSets, 'id');

            $teachers = $this->getMemberService()->findTeachersByCourseIds($courseIds);
            $teacherIds = ArrayToolkit::column($teachers, 'teacherId');
            $teachers = ArrayToolkit::group($teachers, 'teacherId');

            $users = $this->getUserService()->findUsersByIds($teacherIds);
            $users = ArrayToolkit::index($users, 'id');

            $this->sendWarningsTeacher($teachers, $courseSets, $courses, $users, $warnValue);

            $this->sendWarningResponsible($courses, $courseSets, $users, $settingMobile);
        } catch (\Exception $e) {
        }
    }

    protected function sendWarningsTeacher($teachers, $courseSets, $courses, $users, $warnValue)
    {
        foreach ($teachers as $userId => $teacherCourses) {
            $number = $users[$userId]['number'];

            $rows = array();
            $messageTitle = "本周课程活动参与率预警(低于{$warnValue}%)：";

            $flag = 1;
            foreach ($teacherCourses as $teacherCourse) {
                $courseId = $teacherCourse['courseId'];
                if (empty($courses[$courseId])) {
                    continue;
                }
                $course = $courses[$courseId];

                if (empty($courseSets[$course['courseSetId']])) {
                    continue;
                }
                $courseSet = $courseSets[$course['courseSetId']];
                $rows[] = "\n\n{$flag}.{$courseSet['title']}-{$course['title']}";
                $flag++;
            }

            $this->sendMessage($messageTitle, $rows, array($number));
        }
        $courseIds = ArrayToolkit::column($courses, 'id');

        $this->getCourseWarningService()->recordWarning('taskInCompleRate', $courseIds);
    }

    protected function sendWarningResponsible($courses, $courseSets, $users, $settingMobile)
    {
        $warnValue = $settingMobile['taskInCompleWarning'];
        $compleCount = $settingMobile['taskInCompleWarningCount'];
        $warnCourses = $this->getCourseWarningService()->findCourseByTypeAndContinuous('taskInCompleRate', $compleCount);

        $courseIds = ArrayToolkit::column($warnCourses, 'courseId');

        $teachers = $this->getMemberService()->findTeachersByCourseIds($courseIds);
        $teachers = ArrayToolkit::index($teachers, 'courseId');

        $warns = array();
        foreach ($warnCourses as $warnCourse) {
            $courseId = $warnCourse['courseId'];
            if (empty($teachers[$courseId])) {
                continue;
            }
            $userId = $teachers[$courseId]['teacherId'];

            if (empty($users[$userId])) {
                continue;
            }
            $user = $users[$userId];

            if (empty($courses[$courseId])) {
                continue;
            }
            $course = $courses[$courseId];

            if (empty($courseSets[$course['courseSetId']])) {
                continue;
            }
            $courseSet = $courseSets[$course['courseSetId']];

            if (empty($user['orgId'])) {
                continue;
            }
            $warnCourse['teacherNumber'] = $user['number'];
            $warnCourse['teacherNickname'] = $user['nickname'];
            $warnCourse['teachertruename'] = $user['truename'];
            $warnCourse['orgId'] = $user['orgId'];
            $warnCourse['courseSetTitle'] = $courseSet['title'];
            $warnCourse['courseTitle'] = $course['title'];
            $warns[] = $warnCourse;
        }

        $orgCourses = ArrayToolkit::group($warns, 'orgId');
        foreach ($orgCourses as $orgId => $courses) {
            $facultyLeaders = $this->getOrgService()->findFacultyLeadersByOrgId($orgId);
            $leaderUserIds = ArrayToolkit::column($facultyLeaders, 'userId');
            $facultyLeaders = $this->getUserService()->findUsersByIds($leaderUserIds);
            $leaderNumbers = ArrayToolkit::column($facultyLeaders, 'number');

            $rows = array();
            $messageTitle = "本周课程活动参与率预警(连续{$compleCount}次低{$warnValue}%):";
            $flag = 1;
            foreach ($courses as $course) {
                $rows[] = "\n\n{$flag}.{$course['courseSetTitle']}-{$course['courseTitle']}\n主带老师:{$course['teacherNickname']}";
                $flag ++;
            }

            $this->sendMessage($messageTitle, $rows, $leaderNumbers);
        }
    }

    protected function sendMessage($messageTitle, $rows, $toids)
    {
        $weixinClient = $this->getPlatformClient();
        $pages = array_chunk($rows, 10, false);

        foreach ($pages as $page) {
            $message = $messageTitle;
            foreach ($page as $row) {
                $message .= $row;
            }
            $weixinClient->sendTextMessage($toids, $message);
        }
    }

    protected function getPlatformClient()
    {
        $biz = $this->getServiceKernel()->getBiz();
        $weixinClient = PlatformFactory::create($biz);

        return $weixinClient;
    }

    protected function getCourseWarningService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseWarningService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:User:UserService');
    }

    protected function getMemberService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseStatisticsService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Statistics:CourseStatisticsService');
    }

    protected function getOrgService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Org:OrgService');
    }

    protected function getCourseSetService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseSetService');
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
