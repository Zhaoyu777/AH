<?php

namespace CustomBundle\Biz\SignIn\Dao;

interface SignInMemberDao
{
    public function deleteBySignInId($signInId);

    public function getBySignInIdAndUserId($signInId, $userId);

    public function findBySignInId($signInId);

    public function findByLessonIdAndUserId($lessonId, $userId);

    public function findByUserId($userId);

    public function findBySignInIdAndStatus($signinId, $status);

    public function findByCourseId($courseId);

    public function findUniqueMembersByCourseIds($courseIds);
}
