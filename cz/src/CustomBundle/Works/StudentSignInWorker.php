<?php

namespace CustomBundle\Works;

use Codeages\Plumber\IWorker;

class StudentSignInWorker extends AbstractWorker
{
    public function executeProcess($data)
    {
        $signIn = $this->getSignInService()->getSignIn($data['body']['signInId']);

        if (empty($signIn)) {
            return array('code' => IWorker::FINISH);
        }

        $member = $this->getSignInService()->getSignInMemberBySignInIdAndUserId($data['body']['signInId'], $data['body']['userId']);

        if (empty($member)) {
            if ($data['body']['__retry'] > 10) {
                return array('code' => IWorker::FINISH);
            }

            return array('code' => IWorker::RETRY, 'delay' => 1);
        }

        if ($member['status'] == 'attend') {
            return array('code' => IWorker::FINISH);
        }

        $this->getSignInService()->updateStudentSignIn($member['id'], $data['body']['fields']);

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
