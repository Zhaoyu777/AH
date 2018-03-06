<?php

namespace CustomBundle\Works;

use Codeages\Plumber\IWorker;
use Codeages\Biz\Framework\Event\Event;

class TeacherSignInWorker extends AbstractWorker
{
    public function executeProcess($data)
    {
        $signIn = $this->getSignInService()->getSignIn($data['body']['signInId']);

        if (!empty($signIn)) {
            $courseLesson = $this->getCourseLessonService()->getCourseLesson($signIn['lessonId']);
            $students     = $this->getCourseMemberService()->findCourseStudents($courseLesson['courseId'], 0, PHP_INT_MAX);

            $member = array(
                'time'     => $signIn['time'],
                'lessonId' => $courseLesson['id'],
                'signinId' => $signIn['id'],
                'type'     => 'default',
                'courseId' => $signIn['courseId']
            );

            $members = array();

            foreach ($students as $key => $student) {
                $members[$key]                = $member;
                $members[$key]['userId']      = $student['userId'];
                $members[$key]['status']      = 'absent';
                $members[$key]['updatedTime'] = time();
            }

            $this->getSignInService()->batchCreateSignInMembers($members);

            $this->dispatchEvent('push.signIn.create', new Event($signIn));
        }

        return array('code' => IWorker::FINISH);
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
