<?php

namespace CustomBundle\Biz\Course\Service;

use Biz\Course\Service\MemberService as BaseMemberService;

interface MemberService extends BaseMemberService
{
    public function batchBecomeStudents($courseId, $studentIds);

    public function findMembersByIdsWithUserInfo($ids, $withUserInfo);

    public function randStudentByCourseId($courseId, $rand, $exUserIds);

    public function fintStudentsByCourseIdWithSocre($courseId, $start, $limit);

    public function findByCourseIdAndRole($courseId, $role);

    public function findRandomStudentIdsByLessonId($lessonId, $clearUserIds, $count);
}
