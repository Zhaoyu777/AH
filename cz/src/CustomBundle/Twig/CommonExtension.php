<?php

namespace CustomBundle\Twig;

use Codeages\Biz\Framework\Context\Biz;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommonExtension extends \Twig_Extension
{
    /**
     * @var Biz
     */
    protected $biz;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container, Biz $biz)
    {
        $this->container = $container;
        $this->biz = $biz;
    }

    public function getFilters()
    {
        return array(
        );
    }

    public function getFunctions()
    {
        $options = array('is_safe' => array('html'));

        return array(
            new \Twig_SimpleFunction('is_locked_task', array($this, 'isLockedTask')),
            new \Twig_SimpleFunction('is_course_teacher', array($this, 'isCourseTeacher')),
            new \Twig_SimpleFunction('is_weixin', array($this, 'isWeixin')),
            new \Twig_SimpleFunction('ranking', array($this, 'ranking'), $options),
            new \Twig_SimpleFunction('closeToTask', array($this, 'closeToTask')),
            new \Twig_SimpleFunction('is_register_enabled', array($this, 'isRegisterEnabled')),
            new \Twig_SimpleFunction('isTrySite', array($this, 'isTrySite')),
        );
    }

    public function isLockedTask($task)
    {
        $currentUser = ServiceKernel::instance()->getCurrentUser();

        if (!$currentUser->isLogin()) {
            return true;
        }

        if ($currentUser->isAdmin()) {
            return false;
        }

        if ($currentUser['id'] == $task['createdUserId']) {
            return false;
        }

        if ($this->isCourseTeacher($task['courseId'], $currentUser['id'])) {
            return false;
        }

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);

        if ($lessonTask['stage'] == 'before') {
            return false;
        }

        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        if ($lesson['status'] == 'teached') {
            return false;
        }

        if ($lessonTask['stage'] == 'in') {
            $teachers = $this->getCourseMemberService()->findCourseTeachers($lesson['courseId']);
            $teacherIds = ArrayToolkit::column($teachers, 'userId');

            // $results = $this->getTaskService()->findTaskResultsByUserIdsAndTaskId($teacherIds, $task['id']);
            // if (!empty($results)) {
            //     return false;
            // }
        }

        return true;
    }

    public function isWeixin()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }

        return false;
    }

    public function isCourseTeacher($courseId)
    {
        $currentUser = ServiceKernel::instance()->getCurrentUser();

        if ($currentUser->isAdmin()) {
            return true;
        }

        return $this->getCourseMemberService()->isCourseTeacher($courseId, $currentUser['id']);
    }

    protected function getCourseMemberService()
    {
        return $this->biz->service('CustomBundle:Course:MemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->biz->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getUserService()
    {
        return $this->biz->service('User:UserService');
    }

    protected function getTaskService()
    {
        return $this->biz->service('CustomBundle:Task:TaskService');
    }

    public function getName()
    {
        return 'custom_common_twig';
    }

    public function ranking($rate, $role = 'teacher')
    {
        if ($role == 'teacher') {
            $role = '老师';
        } else {
            $role = '学生';
        }

        if (isset($rate['ltRate'])) {
            return "超过 <span class=\"win-percent-num\">{$rate['ltRate']}%</span> 的".$role;
        } elseif (!empty($rate['gtRate'])) {
            return "落后于 <span class=\"lose-little-percent-num\">{$rate['gtRate']}%</span> 的".$role;
        }
    }

    public function isRegisterEnabled()
    {
        return $this->getAuthService()->isRegisterEnabled();
    }

    public function closeToTask($taskId, $type)
    {
        $task = $this->getTaskService()->getCloseToTask($taskId, $type);

        return empty($task) ? false : $task['id'];
    }

    public function isTrySite()
    {
        $magic = $this->getSettingService()->get('magic', array());

        return !empty($magic['is_try_site']) && $magic['is_try_site'] == 1 ? true : false;
    }

    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }

    protected function getAuthService()
    {
        return $this->biz->service('User:AuthService');
    }
}
