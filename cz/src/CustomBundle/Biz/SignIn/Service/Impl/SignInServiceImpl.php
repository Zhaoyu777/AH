<?php

namespace CustomBundle\Biz\SignIn\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Common\BeanstalkClient;
use CustomBundle\Biz\SignIn\Service\SignInService;

class SignInServiceImpl extends BaseService implements SignInService
{
    public function createSignIn($signIn)
    {
        if (!ArrayToolkit::requireds($signIn, array('lessonId', 'time', 'verifyCode', 'courseId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $lesson = $this->getCourseLessonService()->getCourseLesson($signIn['lessonId']);
        if ($lesson['status'] == 'teached') {
            throw $this->createInvalidArgumentException('课次已下课，不能再签到咯');
        }

        if ($signIn['time'] == 2) {
            $first = $this->getSignInByLessonIdAndTime($signIn['lessonId'], 1);
            if ($first['status'] == 'start') {
                throw $this->createInvalidArgumentException('上一签到还没结束');
            }
        }

        $sign = $this->getSignInByLessonIdAndTime($signIn['lessonId'], $signIn['time']);
        if (!empty($sign)) {
            throw $this->createInvalidArgumentException('the sign has been exist');
        }

        $signIn = ArrayToolkit::parts($signIn, array(
            'time',
            'lessonId',
            'verifyCode',
            'courseId',
        ));

        $signIn['status'] = 'start';

        $this->beginTransaction();
        try {
            $created = $this->getSignInDao()->create($signIn);

            $this->commit();
            if ($this->isOpenWorker()) {
                BeanstalkClient::putTubeMessage('TeacherSignInWorker', array('signInId' => $created['id']));
            } else {
                $this->dispatchEvent('signIn.create', new Event($created));
                $this->dispatchEvent('push.signIn.create', new Event($created));
            }
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }


        return $created;
    }

    public function tryManageSignIn($signInId)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('用户没有登录!');
        }

        if (!$user->isTeacher()) {
            throw $this->createAccessDeniedException('您无权访问！');
        }

        $signIn = $this->getSignIn($signInId);

        if (empty($signIn)) {
            throw $this->createNotFoundException();
        }

        return $signIn;
    }

    public function endSignIn($signInId)
    {
        $signIn = $this->getSignInDao()->get($signInId);

        if (empty($signIn)) {
            throw $this->createNotFoundException('the sign in not found');
        }

        $signIn = $this->getSignInDao()->update($signInId, array('status' => 'end'));

        $this->dispatchEvent('signIn.end', new Event($signIn));

        return $signIn;
    }

    public function cancelSignIn($signInId)
    {
        $signIn = $this->verifySignIn($signInId);

        if (empty($signIn)) {
            throw $this->createNotFoundException('the sign in not found');
        }

        if ($signIn['status'] == 'end') {
            throw $this->createAccessDeniedException('该签到已结束，不能取消！');
        }

        $affected = $this->getSignInDao()->delete($signInId);
        if ($this->isOpenWorker()) {
            BeanstalkClient::putTubeMessage('TeacherCancelSignInWorker', array(
                'signIn' => $signIn,
            ));
        } else {
            $this->dispatchEvent('signIn.cancel', new Event($signIn));
        }

        return $affected;
    }

    public function deleteSignInsByLessonId($lessonId)
    {
        $this->beginTransaction();
        try {
            $this->getSignInDao()->deleteByLessonId($lessonId);
            $this->getMemberDao()->deleteByLessonId($lessonId);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getSignIn($signInId)
    {
        return $this->verifySignIn($signInId);
    }

    public function getSignInByLessonIdAndTime($lessonId, $time)
    {
        return $this->getSignInDao()->getByLessonIdAndTime($lessonId, $time);
    }

    public function getLastSignInByLessonId($lessonId)
    {
        $signIn = $this->getSignInDao()->getLastSignInByLessonId($lessonId);

        if (!empty($signIn)) {
            $signIn = $this->verifySignIn($signIn['id']);
        }

        return $signIn;
    }

    public function findEndSignInsByCourseId($courseId)
    {
        return $this->getSignInDao()->findEndSignInsByCourseId($courseId);
    }

    public function findSignInsByLessonId($lessonId)
    {
        return $this->getSignInDao()->findByLessonId($lessonId);
    }

    public function findIngSignInByCourseId($courseId)
    {
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

        if (empty($lessons)) {
            return array();
        }
        $lessonIds = ArrayToolkit::column($lessons, 'id');

        return $this->getSignInDao()->findIngByLessonIds($lessonIds);
    }

    public function createSignInMember($member)
    {
        if (!ArrayToolkit::requireds($member, array('lessonId', 'time', 'signinId', 'userId', 'courseId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $member = ArrayToolkit::parts($member, array(
            'time',
            'userId',
            'lessonId',
            'signinId',
            'type',
            'courseId'
        ));

        $member['status'] = 'absent';
        $member['updatedTime'] = time();
        if (empty($member['type'])) {
            $member['type'] = 'add';
        }

        $this->beginTransaction();
        try {
            $created = $this->getMemberDao()->create($member);

            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function batchCreateSignInMembers($members)
    {
        if (empty($members)) {
            return array();
        }

        return $this->getMemberDao()->batchCreate($members);
    }

    public function setSignInMemberStatus($signInMemberId, $status, $userId)
    {
        $signInMember = $this->getSignInMember($signInMemberId);

        $this->tryManageSignIn($signInMember['signinId']);

        $affected = $this->getMemberDao()->update($signInMemberId, array('status' => $status, 'opUserId' => $userId));

        $this->dispatchEvent('attendance.set', new Event(array_merge($affected, array('originStatus' => $signInMember['status']))));

        return $affected;
    }

    public function attendSignIn($memberId)
    {
        $member = $this->getSignInMember($memberId);

        if (empty($member)) {
            throw $this->createNotFoundException('the sign in member not found');
        }

        return $this->getMemberDao()->update($memberId, array('status' => 'attend'));
    }

    public function absentSignIn($memberId)
    {
        $member = $this->getSignInMember($memberId);

        if (empty($member)) {
            throw $this->createNotFoundException('the sign in member not found');
        }

        return $this->getMemberDao()->update($memberId, array('status' => 'absent'));
    }

    public function studentSignIn($userId, $lessonId, $time, $fields)
    {
        $signIn = $this->getSignInByLessonIdAndTime($lessonId, $time);
        if (!empty($signIn) && ($signIn['createdTime'] + 45 * 60) < time() && ($signIn['status'] != 'end')) {
            $signIn = $this->endSignIn($signIn['id']);
        }

        if (empty($signIn)) {
            throw $this->createNotFoundException('该签到不存在');
        }

        if ($signIn['status'] == 'end') {
            throw $this->createAccessDeniedException('该签到已结束');
        }

        if ($signIn['verifyCode'] != $fields['code']) {
            throw $this->createAccessDeniedException('签到码错误');
        }

        $fields = ArrayToolkit::parts($fields, array(
            'lng',
            'lat',
            'address',
        ));
        $fields['status'] = 'attend';

        if ($this->isOpenWorker()) {
            BeanstalkClient::putTubeMessage('StudentSignInWorker', array(
                'signInId' => $signIn['id'],
                'userId' => $userId,
                'fields' => $fields,
            ));
        } else {
            $member = $this->getSignInMemberBySignInIdAndUserId($signIn['id'], $userId);
            if ($member['status'] == 'attend') {
                return $member;
            }

            return $this->updateStudentSignIn($member['id'], $fields);
        }
    }

    public function updateStudentSignIn($memberId, $fields)
    {
        if (!empty($fields['lat'])) {
            $fields['address'] = $this->getSignInAddress($fields['lat'], $fields['lng']);
        }

        $affected = $this->getMemberDao()->update($memberId, $fields);

        $this->dispatchEvent('signIn.student.attend', new Event($affected));

        return $affected;
    }

    public function getSignInAddress($lat, $lng)
    {
        $url = "http://apis.map.qq.com/ws/geocoder/v1/?location=$lat,$lng&key=D3RBZ-FETWP-CIODL-LPQQ7-J4HO3-66B5K&get_poi=1";
        $data = CurlToolkit::request('GET', $url, array(), array('timeout' => 1));

        if (empty($data['result']['address'])) {
            return '';
        }

        return $data['result']['address'];
    }

    public function deleteSignInMembersBySignInId($signInId)
    {
        return $this->getMemberDao()->deleteBySignInId($signInId);
    }

    public function deleteSignMember($id)
    {
        return $this->getMemberDao()->delete($id);
    }

    public function getSignInMember($memberId)
    {
        return $this->getMemberDao()->get($memberId);
    }

    public function getSignInMemberBySignInIdAndUserId($signInId, $userId)
    {
        return $this->getMemberDao()->getBySignInIdAndUserId($signInId, $userId);
    }

    public function findSignInMembersByUserIdAndLessonIds($userId, $lessonIds)
    {
        return $this->getMemberDao()->findByUserIdAndLessonIds($userId, $lessonIds);
    }

    public function findSignInMembersBySignInId($signInId)
    {
        return $this->getMemberDao()->findBySignInId($signInId);
    }

    public function findSignInMembersByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->getMemberDao()->findByLessonIdAndUserId($lessonId, $userId);
    }

    public function findSignInMembersByUserId($userId)
    {
        return $this->getMemberDao()->findByUserId($userId);
    }

    public function findSignInMembersBySignInIdAndStatus($signinId, $status)
    {
        return $this->getMemberDao()->findBySignInIdAndStatus($signinId, $status);
    }

    public function findSignInMembersByLessonIdAndTimeAndStatus($lessonId, $time, $status, $count = PHP_INT_MAX)
    {
        return $this->getMemberDao()->findByLessonIdAndTimeAndStatus($lessonId, $time, $status, $count);
    }

    public function countSignInMembers($conditions)
    {
        return $this->getMemberDao()->count($conditions);
    }

    public function analysisSignInByLessonId($lessonId)
    {
        /**
         * 该数组顺序决定签到结果统计的各个优先级:
         * 逻辑为：发起一次签到时根据第一次签到结果统计，若发起2次签到，结果优先级为缺勤>请假>迟到>早退>出勤
         */
        $results = array(
            'absent' => 0,
            'leave' => 0,
            'late' => 0,
            'early' => 0,
            'attend' => 0,
        );
        $first = $this->getSignInByLessonIdAndTime($lessonId, 1);
        if (empty($first)) {
            $results['all'] = 0;

            return $results;
        }

        $firstMembers = $this->findSignInMembersBySignInId($first['id']);
        $memberCount = $this->getCourseMemberService()->countMembers(array(
            'role' => 'student',
            'courseId' => $first['courseId'],
        ));
        $second = $this->getSignInByLessonIdAndTime($lessonId, 2);
        $firstMembers = ArrayToolkit::group($firstMembers, 'status');
        if (empty($second)) {
            array_walk(
                $results,
                function (&$result, $key) use ($firstMembers) {
                    if (!empty($firstMembers[$key])) {
                        $result = count($firstMembers[$key]);
                    }
                }
            );
            $results['all'] = $memberCount;

            return $results;
        }

        $firstUserIds = array(
            'attend' => array(),
            'absent' => array(),
            'late' => array(),
            'early' => array(),
            'leave' => array(),
        );
        foreach ($firstMembers as $status => $statusMembers) {
            $firstUserIds[$status] = ArrayToolkit::column($statusMembers, 'userId');
        }

        $secondMembers = $this->findSignInMembersBySignInId($second['id']);
        $secondMembers = ArrayToolkit::group($secondMembers, 'status');

        $secondUserIds = array(
            'attend' => array(),
            'absent' => array(),
            'late' => array(),
            'early' => array(),
            'leave' => array(),
        );
        foreach ($secondMembers as $status => $statusMembers) {
            $secondUserIds[$status] = ArrayToolkit::column($statusMembers, 'userId');
        }

        $excludeUserIds = array();
        foreach ($results as $status => $result) {
            $currentUserIds = array_merge($firstUserIds[$status], $secondUserIds[$status]);
            $currentUserIds = array_unique($currentUserIds);
            $results[$status] = count(array_diff($currentUserIds, $excludeUserIds));

            $excludeUserIds = array_merge($excludeUserIds, $currentUserIds);
            $excludeUserIds = array_unique($excludeUserIds);
        }

        $results['attend'] = count(array_intersect($firstUserIds['attend'], $secondUserIds['attend']));

        $results['all'] = $memberCount;

        return $results;
    }

    public function countSignContinuousByUserId($userId)
    {
        $signIns = array_reverse($this->getMemberDao()->findByUserId($userId));

        $count = 0;
        foreach ($signIns as $signIn) {
            if ($signIn['status'] != 'attend') {
                break ;
            }
            $count++;
        }

        return $count;
    }

    public function getRealAttendMemberCountByLessonId($lessonId)
    {
        $signIn = $this->getSignInByLessonIdAndTime($lessonId, 2);
        if (empty($signIn)) {
            return 0;
        }

        $firstMembers = $this->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 1, 'attend');
        $firstUserIds = ArrayToolkit::column($firstMembers, 'userId');

        $secondMembers = $this->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 2, 'attend');
        $secondUserIds = ArrayToolkit::column($secondMembers, 'userId');

        return count(array_intersect($firstUserIds, $secondUserIds));
    }

    public function verifySignIn($signInId)
    {
        $signIn = $this->getSignInDao()->get($signInId);

        if (!empty($signIn) && ($signIn['createdTime'] + 45 * 60) < time() && ($signIn['status'] != 'end')) {
            $signIn = $this->endSignIn($signIn['id']);
        }

        return $signIn;
    }

    public function findLessonsNeedSignInTimesByCourseId($courseId)
    {
        $signIns = $this->getSignInDao()->findByCourseId($courseId);
        $lessonIds = ArrayToolkit::column($signIns, 'lessonId');

        return array_count_values($lessonIds);
    }

    public function findStudentLessonsActualSignInTimesByCourseId($courseId)
    {
        $signIns = $this->getMemberDao()->findByCourseId($courseId);
        $signInGroups = ArrayToolkit::group($signIns, 'userId');

        $results = array();
        foreach ($signInGroups as $key => $userSignIns) {
            $userSignIns = ArrayToolkit::column($userSignIns, 'lessonId');
            $results[$key] = array_count_values($userSignIns);
        }

        return $results;
    }

    protected function isOpenWorker()
    {
        $magic = $this->createService('System:SettingService')->get('magic');

        if (isset($magic['open_worker']) && $magic['open_worker']) {
            return true;
        }

        return false;
    }

    public function updateKeepAttendAndAbsentTimesByLessonId($lessonId)
    {
        $signIns = $this->findSignInsByLessonId($lessonId);

        if (empty($signIns)) {
            return ;
        } else if (count($signIns) === 1) {
            $attendMembers = $this->findSignInMembersBySignInIdAndStatus(reset($signIns)['id'], 'attend');
            $attendUserIds = ArrayToolkit::column($attendMembers, 'userId');

            $absentMembers = $this->findSignInMembersBySignInIdAndStatus(reset($signIns)['id'], 'absent');
            $absentUserIds = ArrayToolkit::column($absentMembers, 'userId');
        } else if (count($signIns) === 2) {
            $firstAttendMembers = $this->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 1, 'attend');
            $secondAttendMembers = $this->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 2, 'attend');
            $firstAttendUserIds = ArrayToolkit::column($firstAttendMembers, 'userId');
            $secondAttendUserIds = ArrayToolkit::column($secondAttendMembers, 'userId');
            $attendUserIds = array_intersect($firstAttendUserIds, $secondAttendUserIds);

            $firstAbsentMembers = $this->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 1, 'absent');
            $secondAbsentMembers = $this->findSignInMembersByLessonIdAndTimeAndStatus($lessonId, 2, 'absent');
            $firstAbsentUserIds = ArrayToolkit::column($firstAbsentMembers, 'userId');
            $secondAbsentUserIds = ArrayToolkit::column($secondAbsentMembers, 'userId');

            $absentUserIds = array_merge($firstAbsentUserIds, $secondAbsentUserIds);
            $absentUserIds = array_unique($absentUserIds);
        }

        if (!empty($attendUserIds)) {
            $this->getWarningDao()->waveByUserId($attendUserIds, array('keepAttendTimes' => +1));
            $updateFileds = array();
            foreach ($attendUserIds as $attendUserId) {
                $updateFileds[] = array('keepAbsentTimes' => 0);
            }
            $this->getWarningDao()->batchUpdate($attendUserIds, $updateFileds, 'userId');
        }

        if (!empty($absentUserIds)) {
            $this->getWarningDao()->waveByUserId($absentUserIds, array('keepAbsentTimes' => +1));
            $fields = array();
            foreach ($absentUserIds as $absentUserId) {
                $fields[] = array('keepAttendTimes' => 0);
            }
            $this->getWarningDao()->batchUpdate($absentUserIds, $fields, 'userId');
        }
    }

    public function findWarningList($absentTimes)
    {
        return $this->getWarningDao()->findWarningList($absentTimes);
    }

    public function initWarning($userId)
    {
        return $this->getWarningDao()->create(array(
            'userId' => $userId,
        ));
    }

    public function findByLessonIds($lessonIds)
    {
        if (empty($lessonIds)) {
            return array();
        }

        return $this->getSignInDao()->findEndByLessonIds($lessonIds);
    }

    public function countSignInsByCourseIdGroupUserIdBeforeTime($courseId, $time)
    {
        $counts = $this->getMemberDao()->countByCourseIdGroupUserIdBeforeTime($courseId, $time);

        return ArrayToolkit::index($counts, 'userId');
    }

    public function findSignInsMemberByUserIdAndCourseIdAndStatus($userId, $courseId, $status)
    {
        return $this->getMemberDao()->findByUserIdAndCourseIdAndStatus($userId, $courseId, $status);
    }

    public function findSignInMembersByCourseIds($courseIds)
    {
        return $this->getMemberDao()->findUniqueMembersByCourseIds($courseIds);
    }

    protected function getCourseMemberService()
    {
        return $this->biz->service('Course:MemberService');
    }

    protected function getSignInDao()
    {
        return $this->createDao('CustomBundle:SignIn:SignInDao');
    }

    protected function getMemberDao()
    {
        return $this->createDao('CustomBundle:SignIn:SignInMemberDao');
    }

    protected function getCourseLessonService()
    {
        return $this->biz->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getWarningDao()
    {
        return $this->createDao('CustomBundle:SignIn:SignInWarningDao');
    }
}
