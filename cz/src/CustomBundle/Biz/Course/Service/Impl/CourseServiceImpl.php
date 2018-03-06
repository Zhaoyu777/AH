<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\CourseService;
use Biz\Course\Service\Impl\CourseServiceImpl as BaseCourseServiceImpl;

class CourseServiceImpl extends BaseCourseServiceImpl implements CourseService
{
    public function createCourse($course)
    {
        if (!ArrayToolkit::requireds($course, array('title', 'courseSetId', 'expiryMode', 'learnMode'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        if (!in_array($course['learnMode'], array('freeMode', 'lockMode'))) {
            throw $this->createInvalidArgumentException('Param Invalid: LearnMode');
        }

        if (!isset($course['isDefault'])) {
            $course['isDefault'] = 0;
        }

        $course = ArrayToolkit::parts($course, array(
            'title',
            'about',
            'courseSetId',
            'learnMode',
            'expiryMode',
            'expiryDays',
            'expiryStartDate',
            'serializeMode',
            'expiryEndDate',
            'isDefault',
            'type',
            'isFree',
            'serializeMode',
            'termCode',
            'summary',
            'courseType'
        ));

        if (!isset($course['isFree'])) {
            $course['isFree'] = 1;
        }

        $course = $this->validateExpiryMode($course);

        $course['status']  = 'published';
        $course['creator'] = $this->getCurrentUser()->getId();

        $this->beginTransaction();
        try {
            $created     = $this->getCourseDao()->create($course);
            $currentUser = $this->getCurrentUser();

            $this->getMemberService()->setCourseTeachers($created['id'], array(
                array(
                    'id'        => $currentUser['id'],
                    'isVisible' => 1
                )
            ));

            $this->commit();

            $this->dispatchEvent('instant.course.create', new Event($created));

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function tryStartCourse($courseId, $courseSetId = 0)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        $course = $this->getCourseDao()->get($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException("Course#{$courseId} Not Found");
        }
        if ($courseSetId > 0 && $course['courseSetId'] !== $courseSetId) {
            throw $this->createInvalidArgumentException(
                "Invalid Argument: Course#{$courseId} not in CoruseSet#{$courseSetId}"
            );
        }

        if ($course['parentId'] > 0) {
            $classroom = $this->getClassroomService()->getClassroomByCourseId($courseId);
            if (!empty($classroom) && $classroom['headTeacherId'] == $user['id']) {
                //班主任有权管理班级下所有课程
                return $course;
            }
        }

        if (!$this->hasCourseStartRole($courseId)) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        return $course;
    }

    public function hasCourseStartRole($courseId = 0)
    {
        $user = $this->getCurrentUser();
        //未登录，无权限管理
        if (!$user->isLogin()) {
            return false;
        }

        $course = $this->getCourse($courseId);
        //课程不存在，无权限管理
        if (empty($course)) {
            return false;
        }

        if ($course['creator'] == $user->getId()) {
            return true;
        }

        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        if ($user->getId() == $courseSet['creator']) {
            return true;
        }

        $teacher = $this->getMemberService()->isCourseTeacher($courseId, $user->getId());
        //不是课程教师，无权限管理
        if ($teacher) {
            return true;
        }

        if ($course['parentId'] > 0) {
            $classroomRef = $this->getClassroomService()->getClassroomCourseByCourseSetId($course['courseSetId']);
            if (!empty($classroomRef)) {
                $isTeacher = $this->getClassroomService()->isClassroomTeacher(
                    $classroomRef['classroomId'],
                    $user['id']
                );
                $isHeadTeacher = $this->getClassroomService()->isClassroomHeadTeacher(
                    $classroomRef['classroomId'],
                    $user['id']
                );
                if ($isTeacher || $isHeadTeacher) {
                    return true;
                }
            }
        }

        return false;
    }

    public function findNotClosedCourseCountsBySetIdsAndTeacherId($courseSetIds, $teacherId)
    {
        if (empty($courseSetIds)) {
            return array();
        }

        return ArrayToolkit::index($this->getCourseDao()->findNotClosedCourseCountsBySetIdsAndTeacherId($courseSetIds, $teacherId), 'courseSetId');
    }

    public function findInstantCoursesByIds($ids)
    {
        $courses = $this->getCourseDao()->findInstantCoursesByIds($ids);

        return ArrayToolkit::index($courses, 'id');
    }

    public function findInstantCoursesByUserId($userId)
    {
        $member = $this->getMemberDao()->findByUserId($userId);
        $courseIds = ArrayToolkit::column($member, 'courseId');
        return $this->findInstantCoursesByIds($courseIds);
    }

    public function findInstantCoursesByIdsAndTermCode($ids, $termCode)
    {
        $courses = $this->getCourseDao()->findInstantCoursesByIdsAndTermCode($ids, $termCode);

        return ArrayToolkit::index($courses, 'id');
    }

    public function findInstantCoursesByTermCode($termCode)
    {
        $courses = $this->getCourseDao()->findInstantCoursesByTermCode($termCode);

        return ArrayToolkit::index($courses, 'id');
    }

    public function findSchoolCoursesByIdsAndTermCode($ids, $termCode)
    {
        $courses = $this->getCourseDao()->findSchoolCoursesByIdsAndTermCode($ids, $termCode);

        return ArrayToolkit::index($courses, 'id');
    }

    public function findStudentCountsByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }

        return ArrayToolkit::index($this->getMemberDao()->findStudentCountsByCourseIds($courseIds), 'courseId');
    }

    public function findNotClosedCoursesByTeacherId($teacherId)
    {
        return $this->getCourseDao()->findNotClosedCoursesByTeacherId($teacherId);
    }

    public function findNotClosedCoursesByTeacherIdAndTermCode($teacherId, $termCode)
    {
        return $this->getCourseDao()->findNotClosedCoursesByTeacherIdAndTermCode($teacherId, $termCode);
    }

    public function findInstantCourseIdByTeacherId($teacherId)
    {
        return $this->getCourseDao()->findInstantCourseIdByTeacherId($teacherId);
    }

    protected function _prepareCourseConditions($conditions)
    {
        $conditions = array_filter(
            $conditions,
            function ($value) {
                if ($value == 0) {
                    return true;
                }

                return !empty($value);
            }
        );

        if (isset($conditions['date'])) {
            $dates = array(
                'yesterday'  => array(
                    strtotime('yesterday'),
                    strtotime('today')
                ),
                'today'      => array(
                    strtotime('today'),
                    strtotime('tomorrow')
                ),
                'this_week'  => array(
                    strtotime('Monday this week'),
                    strtotime('Monday next week')
                ),
                'last_week'  => array(
                    strtotime('Monday last week'),
                    strtotime('Monday this week')
                ),
                'next_week'  => array(
                    strtotime('Monday next week'),
                    strtotime('Monday next week', strtotime('Monday next week'))
                ),
                'this_month' => array(
                    strtotime('first day of this month midnight'),
                    strtotime('first day of next month midnight')
                ),
                'last_month' => array(
                    strtotime('first day of last month midnight'),
                    strtotime('first day of this month midnight')
                ),
                'next_month' => array(
                    strtotime('first day of next month midnight'),
                    strtotime('first day of next month midnight', strtotime('first day of next month midnight'))
                )
            );

            if (array_key_exists($conditions['date'], $dates)) {
                $conditions['startTimeGreaterThan'] = $dates[$conditions['date']][0];
                $conditions['startTimeLessThan']    = $dates[$conditions['date']][1];
                unset($conditions['date']);
            }
        }

        if (isset($conditions['creator']) && !empty($conditions['creator'])) {
            $user                 = $this->getUserService()->getUserByNickname($conditions['creator']);
            $conditions['userId'] = $user ? $user['id'] : -1;
            unset($conditions['creator']);
        }

        if (isset($conditions['categoryId'])) {
            $conditions['categoryIds'] = array();

            if (!empty($conditions['categoryId'])) {
                $childrenIds               = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
                $conditions['categoryIds'] = array_merge(array($conditions['categoryId']), $childrenIds);
            }

            unset($conditions['categoryId']);
        }

        if (isset($conditions['nickname'])) {
            $user                 = $this->getUserService()->getUserByNickname($conditions['nickname']);
            $conditions['userId'] = $user ? $user['id'] : -1;
            unset($conditions['nickname']);
        }

        if (isset($conditions['teacherId'])) {
            $conditions['teacherId'] = "%|{$conditions['teacherId']}|%";
        }

        return $conditions;
    }

    public function createChapter($chapter)
    {
        if (!in_array($chapter['type'], array('chapter', 'unit', 'lesson'))) {
            throw $this->createInvalidArgumentException('Invalid Chapter Type');
        }

        if (in_array($chapter['type'], array('unit', 'lesson'))) {
            list($chapter['number'], $chapter['parentId']) = $this->getNextNumberAndParentId($chapter['courseId']);
        } else {
            $chapter['number']   = $this->getNextChapterNumber($chapter['courseId']);
            $chapter['parentId'] = 0;
        }

        if (empty($chapter['seq'])) {
            $chapter['seq'] = $this->getNextCourseItemSeq($chapter['courseId']);
        }

        $chapter['createdTime'] = time();

        $chapter = $this->getChapterDao()->create($chapter);

        $this->dispatchEvent('course.chapter.create', new Event($chapter));

        return $chapter;
    }

    public function findChaptersByLessonId($lessonId)
    {
        return $this->getChapterDao()->findChaptersByLessonId($lessonId);
    }

    public function countChapterByChapterId($chapterId)
    {
        $chapter = $this->getChapterDao()->get($chapterId);
        $count = $this->getChapterDao()->countChapterByLessonIdAndSeq($chapter['lessonId'], $chapter['seq']) + 1;

        return $count;
    }

    public function updateChapter($courseId, $chapterId, $fields)
    {
        $this->tryManageCourse($courseId);
        $chapter = $this->getChapterDao()->get($chapterId);

        if (empty($chapter) || $chapter['courseId'] != $courseId) {
            throw $this->createNotFoundException("Chapter#{$chapterId} Not Found");
        }

        $fields = ArrayToolkit::parts($fields, array('title', 'number', 'seq', 'parentId', 'lessonId', 'stage'));

        $chapter = $this->getChapterDao()->update($chapterId, $fields);
        $this->dispatchEvent('course.chapter.update', new Event($chapter));

        return $chapter;
    }

    public function getNextCourseLessonItemSeq($courseId, $lessonId, $parentId = 0, $stage = 'in')
    {
        if ($parentId != 0) {
            $chapterMaxSeq = $this->getChapterDao()->getChapterMaxSeqByLessonIdAndStageAndParentId($lessonId, $parentId, $stage);

            if ($chapterMaxSeq == 0) {
                $parentChapter = $this->getChapterDao()->get($parentId);
                $chapterMaxSeq = $parentChapter['seq'];
            }
        } else {
            $chapterMaxSeq = $this->getNextCourseItemSeq($courseId);
        }

        return $chapterMaxSeq + 1;
    }

    public function getFirstInClassTaskChapterByLessonId($lessonId)
    {
        return $this->getChapterDao()->getFirstInClassTaskChapterByLessonId($lessonId);
    }

    public function getFirstClassTaskChapterByLessonId($lessonId)
    {
        $chapter = $this->getFirstBeforeClassTaskChapterByLessonId($lessonId);

        if (empty($chapter)) {
            $chapter = $this->getFirstInClassTaskChapterByLessonId($lessonId);
        }

        return $chapter;
    }

    public function getFirstBeforeClassTaskChapterByLessonId($lessonId)
    {
        return $this->getChapterDao()->getFirstBeforeClassTaskChapterByLessonId($lessonId);
    }

    public function getFirstUnFinishedBeforeClassTaskChapterByLessonId($lessonId, $userId)
    {
        return $this->getChapterDao()->getFirstUnFinishedBeforeClassTaskChapterByLessonId($lessonId, $userId);
    }

    public function getFirstAfterClassTaskChapterByLessonId($lessonId)
    {
        return $this->getChapterDao()->getFirstAfterClassTaskChapterByLessonId($lessonId);
    }

    public function getFirstUnFinishedAfterClassTaskChapterByLessonId($lessonId, $userId)
    {
        return $this->getChapterDao()->getFirstUnFinishedAfterClassTaskChapterByLessonId($lessonId, $userId);
    }

    public function deleteCoursesByCourseSetId($courseSetId)
    {
        $courses = $this->findCoursesByCourseSetId($courseSetId);

        foreach ($courses as $course) {
            $this->deleteCourse($course['id']);
        }
    }

    public function deleteCourse($courseId)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException('Course not found');
        }

        if ($course['type'] == 'instant') {
            $fields = array(
                'status' => 'delete'
            );

            return $this->getCourseDao()->update($courseId, $fields);
        } else {
            return $this->getCourseDao()->delete($courseId);
        }
    }

    public function getCurrentTerm()
    {
        return $this->getTermDao()->getCurrentTerm();
    }

    public function getTermByShortCode($code)
    {
        return $this->getTermDao()->getByShortCode($code);
    }

    public function sortLessonItems($courseId, $lessonId, $ids)
    {
        $course = $this->tryManageCourse($courseId);

        $parentChapters = array(
            'lesson' => array(),
            'unit' => array(),
            'chapter' => array(),
        );

        $chapterTypes = array('chapter' => 3, 'unit' => 2, 'lesson' => 1);
        $lessonChapterTypes = array();
        $seq = 0;
        $parentId = 0;

        foreach ($ids as $key => $id) {
            if (strpos($id, 'chapter') !== 0) {
                continue;
            }
            $id = str_replace('chapter-', '', $id);
            $chapter = $this->getChapterDao()->get($id);
            ++$seq;

            $index = $chapterTypes[$chapter['type']];

            switch ($index) {
                case 3:
                    $fields['parentId'] = 0;
                    $parentId = $id;
                    $seq += 40;
                    break;
                case 2:
                    if (!empty($parentChapters['chapter'])) {
                        $fields['parentId'] = $parentChapters['chapter']['id'];
                    }
                    break;
                case 1:
                    if (!empty($parentChapters['unit'])) {
                        $fields['parentId'] = $parentChapters['unit']['id'];
                    } elseif (!empty($parentChapters['chapter'])) {
                        $fields['parentId'] = $parentChapters['chapter']['id'];
                    }
                    $fields['parentId'] = $parentId;
                    break;
                default:
                    break;
            }
            $fields = array('seq' => $seq);
            if (!empty($parentChapters[$chapter['type']])) {
                $fields['number'] = $parentChapters[$chapter['type']]['number'] + 1;
            } else {
                $fields['number'] = 1;
            }

            foreach ($chapterTypes as $type => $value) {
                if ($value < $index) {
                    $parentChapters[$type] = array();
                }
            }

            $chapter = $this->updateChapter($courseId, $id, $fields);
            if ($chapter['type'] == 'lesson') {
                array_push($lessonChapterTypes, $chapter);
            }
            $parentChapters[$chapter['type']] = $chapter;
        }

        uasort(
            $lessonChapterTypes,
            function ($lesson1, $lesson2) {
                return $lesson1['seq'] > $lesson2['seq'];
            }
        );
        $taskNumber = 1;
        foreach ($lessonChapterTypes as $key => $chapter) {
            $tasks = $this->getTaskService()->findTasksByChapterId($chapter['id']);
            $tasks = ArrayToolkit::index($tasks, 'mode');
            foreach ($tasks as $task) {
                $seq = $this->getTaskSeq($task['mode'], $chapter['seq']);
                $fields = array(
                    'seq' => $seq,
                    'categoryId' => $chapter['id'],
                    'number' => $taskNumber,
                );
                $this->getTaskService()->updateSeq($task['id'], $fields);
                if ($task['mode'] == 'lesson') {
                    ++$taskNumber;
                }
            }
        }
    }

    protected function getTaskSeq($taskMode, $chapterSeq)
    {
        $taskModes = array('preparation' => 1, 'lesson' => 2, 'exercise' => 3, 'homework' => 4, 'extraClass' => 5);
        if (!array_key_exists($taskMode, $taskModes)) {
            throw new InvalidArgumentException('task mode is invalida');
        }

        return $chapterSeq + $taskModes[$taskMode];
    }

    public function updateInstantChapter($courseId, $chapterId, $fields)
    {
        $this->tryManageCourse($courseId);
        $chapter = $this->getChapterDao()->get($chapterId);

        if (empty($chapter) || $chapter['courseId'] != $courseId) {
            throw $this->createNotFoundException("Chapter#{$chapterId} Not Found");
        }

        $fields = ArrayToolkit::parts($fields, array('title', 'number', 'seq', 'parentId'));

        $chapter = $this->getChapterDao()->update($chapterId, $fields);
        $this->dispatchEvent('course.chapter.update', new Event($chapter));

        return $chapter;
    }

    public function addTerm($term)
    {
        return $this->getTermDao()->create($term);
    }

    public function findTerms()
    {
        return $this->getTermDao()->findAll();
    }

    public function findLecturersByCourseId($courseId)
    {
        $lecturers = ArrayToolkit::index($this->getApiCourseDao()->search(array('courseId' => $courseId), array('zjjs' => 'ASC'), 0, PHP_INT_MAX), 'zjjs');

        return empty($lecturers) ? array() : $lecturers;
    }

    public function findMasterTeachersByCourseId($courseId)
    {
        return $this->getApiCourseDao()->findMasterTeachersByCourseId($courseId);
    }

    public function findAssistantTeachersByCourseId($courseId)
    {
        return $this->getApiCourseDao()->findAssistantTeachersByCourseId($courseId);
    }

    public function findNormalCoursesByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }

        $courses = $this->getCourseDao()->findNormalCoursesByIds($ids);

        return ArrayToolkit::index($courses, 'id');
    }

    public function sortImportCourses($ids)
    {
        if (empty($ids)) {
            return array();
        }
        $courses = $this->findInstantCoursesByIds($ids);

        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        $term = $this->findTerms();
        $term = ArrayToolkit::index($term, 'shortCode');

        $result = array();
        foreach ($courses as $key => $course) {
            $result[] = array(
                'id' => $course['id'],
                'count' => $courseSets[$course['courseSetId']]['title'].' | '.$term[$course['termCode']]['title'].' '.$course['title']
            );
        }

        return $result;
    }

    protected function fillMembersWithUserInfo($members)
    {
        if (empty($members)) {
            return $members;
        }

        $userIds = ArrayToolkit::column($members, 'userId');
        $user = $this->getUserService()->findUsersByIds($userIds);
        $userMap = ArrayToolkit::index($user, 'id');
        foreach ($members as $index => $member) {
            $member['nickname'] = $userMap[$member['userId']]['nickname'];
            $member['number'] = $userMap[$member['userId']]['number'];
            $member['smallAvatar'] = $userMap[$member['userId']]['smallAvatar'];
            $members[$index] = $member;
        }

        return $members;
    }

    public function getNextNumberAndParentId($courseId)
    {
        $lastChapter = $this->getChapterDao()->getLastChapterByCourseIdAndType($courseId, 'chapter');

        $parentId = empty($lastChapter) ? 0 : $lastChapter['id'];

        $num = 1 + $this->getChapterDao()->getChapterCountByCourseIdAndTypeAndParentId($courseId, 'unit', $parentId);

        return array($num, $parentId);
    }

    public function countInstantCourseByUserIdsAndTermCodeAndRoleGroupUserId($userIds, $termCode, $role)
    {
        if (empty($userIds)) {
            return array();
        }

        return ArrayToolkit::index($this->getCourseDao()->countInstantCourseByUserIdsAndTermCodeAndRoleGroupUserId($userIds, $termCode, $role), 'userId');
    }

    public function findInstantCoursesByUserIdAndTermCodeAndRole($userId, $termCode, $role)
    {
        return $this->getCourseDao()->findInstantCoursesByUserIdAndTermCodeAndRole($userId, $termCode, $role);
    }

    public function findChapterByCourseIdAndLessonId($courseId, $lessonId)
    {
        return $this->getChapterDao()->findChapterByCourseIdAndLessonId($courseId, $lessonId);
    }

    public function getNextCourseItemSeq($courseId)
    {
        $chapterMaxSeq = $this->getChapterDao()->getChapterMaxSeqByCourseId($courseId);
        $taskMaxSeq = $this->getTaskService()->getMaxSeqByCourseId($courseId);

        return ($chapterMaxSeq > $taskMaxSeq ? $chapterMaxSeq : $taskMaxSeq) + 1;
    }

    public function isAnyLessonStart($courseId)
    {
        $teachingLessons = $this->getCourseLessonService()->findTeachingLessonsByCourseId($courseId);

        if (empty($teachingLessons)) {
            return false;
        } else {
            return true;
        }
    }

    public function countAllTeachersByOrgCode($orgCode)
    {
        $term = $this->getCurrentTerm();

        return $this->getApiCourseDao()->countAllTeachersByOrgCodeAndTermCode($orgCode, $term['shortCode']);
    }

    public function findAllCourseMasterTeachers()
    {
        $ids = $this->getMemberDao()->findTeacherCourseIds();
        $ids = ArrayToolkit::column($ids, 'id');
        return $this->getMemberDao()->findAllCourseMasterTeachersByIds($ids);
    }

    protected function getNextChapterNumber($courseId)
    {
        //有逻辑缺陷
        $counter = $this->getChapterDao()->getChapterCountByCourseIdAndType($courseId, 'chapter');

        return $counter + 1;
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getApiCourseDao()
    {
        return $this->createDao('CustomBundle:Course:ApiCourseDao');
    }

    protected function getTermDao()
    {
        return $this->createDao('CustomBundle:Course:TermDao');
    }

    protected function getCourseDao()
    {
        return $this->createDao('CustomBundle:Course:CourseDao');
    }

    protected function getMemberDao()
    {
        return $this->createDao('CustomBundle:Course:CourseMemberDao');
    }

    protected function getChapterDao()
    {
        return $this->createDao('CustomBundle:Course:CourseChapterDao');
    }
}
