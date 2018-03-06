<?php

namespace CustomBundle\Biz\SignIn\Event;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Codeages\PluginBundle\Event\EventSubscriber;

class CustomSignInSubscriber extends EventSubscriber
{
    public static function getSubscribedEvents()
    {
        return array(
            'signIn.create' => 'onSignInCreate',
            'signIn.cancel' => 'onSignInCancel',
            'signIn.end' => 'onSignInEnd'
        );
    }

    public function onSignInCreate(Event $event)
    {
        $signIn = $event->getSubject();

        $courseLesson = $this->getCourseLessonService()->getCourseLesson($signIn['lessonId']);
        $students = $this->getCourseMemberService()->findCourseStudents($courseLesson['courseId'], 0, PHP_INT_MAX);

        $member = array(
            'time' => $signIn['time'],
            'lessonId' => $courseLesson['id'],
            'signinId' => $signIn['id'],
            'type' => 'default',
            'courseId' => $signIn['courseId']
        );

        foreach ($students as $student) {
            $member['userId'] = $student['userId'];
            $this->getSignInService()->createSignInMember($member);
        }
    }

    public function onSignInCancel(Event $event)
    {
        $signIn = $event->getSubject();

        $this->getSignInService()->deleteSignInMembersBySignInId($signIn['id']);
    }

    public function onSignInEnd(Event $event)
    {
        $signIn = $event->getSubject();

        $courseLesson = $this->getCourseLessonService()->getCourseLesson($signIn['lessonId']);
        $course = $this->getCourseService()->getCourse($courseLesson['courseId']);

        $isGain = $this->getTeacherScoreService()->isGainScoreByLessonIdAndSource($signIn['lessonId'], 'signIn');
        if ($isGain) {
            return ;
        }

        $teacherScore = array(
            'lessonId' => $courseLesson['id'],
            'courseId' => $courseLesson['courseId'],
            'type' => 'auto',
            'term' => $course['termCode'],
            'score' => 1,
            'source' => 'signIn',
            'remark' => "课程: {$course['title']}-课次{$courseLesson['number']}-签到得分",
        );

        $this->getTeacherScoreService()->createTeacherScore($teacherScore);
    }

    protected function getSignInService()
    {
        return $this->getBiz()->service('CustomBundle:SignIn:SignInService');
    }

    protected function getCourseMemberService()
    {
        return $this->getBiz()->service('CustomBundle:Course:MemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getCrontabService()
    {
        return $this->getBiz()->service('Crontab:CrontabService');
    }

    protected function getTeacherScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:TeacherScoreService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseService');
    }
}
