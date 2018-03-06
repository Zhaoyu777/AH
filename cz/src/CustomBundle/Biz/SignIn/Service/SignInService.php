<?php

namespace CustomBundle\Biz\SignIn\Service;

interface SignInService
{
    public function createSignIn($signIn);

    public function endSignIn($signInId);

    public function cancelSignIn($signInId);

    public function getSignIn($signInId);

    public function getSignInByLessonIdAndTime($lessonId, $time);

    public function findSignInsByLessonId($lessonId);

    public function createSignInMember($member);

    public function setSignInMemberStatus($signInMemberId, $status, $userId);

    public function attendSignIn($memberId);

    public function absentSignIn($memberId);

    public function deleteSignInMembersBySignInId($signInId);

    public function studentSignIn($userId, $lessonId, $time, $fields);

    public function getSignInMember($memberId);

    public function getSignInMemberBySignInIdAndUserId($signInId, $userId);

    public function findSignInMembersBySignInId($signInId);

    public function findSignInMembersByLessonIdAndUserId($lessonId, $userId);

    public function findSignInMembersByUserId($userId);

    public function findSignInMembersBySignInIdAndStatus($signinId, $status);

    public function countSignInMembers($conditions);

    public function deleteSignMember($id);

    public function findLessonsNeedSignInTimesByCourseId($courseId);

    public function findStudentLessonsActualSignInTimesByCourseId($courseId);

    public function findSignInMembersByCourseIds($courseIds);
}
