<?php

namespace Biz\OpenCourse\Service\Impl;

use Biz\BaseService;
use Biz\Course\Dao\FavoriteDao;
use AppBundle\Common\ArrayToolkit;
use Biz\System\Service\LogService;
use Biz\OpenCourse\Dao\OpenCourseDao;
use Codeages\Biz\Framework\Event\Event;
use Biz\OpenCourse\Dao\OpenCourseLessonDao;
use Biz\OpenCourse\Dao\OpenCourseMemberDao;
use Biz\OpenCourse\Service\OpenCourseService;

class OpenCourseServiceImpl extends BaseService implements OpenCourseService
{
    /**
     * open_course.
     */
    public function getCourse($id)
    {
        return $this->getOpenCourseDao()->get($id);
    }

    public function findCoursesByIds(array $ids)
    {
        return $this->getOpenCourseDao()->findByIds($ids);
    }

    public function searchCourses($conditions, $orderBy, $start, $limit)
    {
        $conditions = $this->_prepareCourseConditions($conditions);

        return $this->getOpenCourseDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function countCourses($conditions)
    {
        return $this->getOpenCourseDao()->count($conditions);
    }

    public function createCourse($course)
    {
        $this->tryCreateCourse();

        if (!ArrayToolkit::requireds($course, array('title'))) {
            throw $this->createServiceException('缺少必要字段，创建课程失败！');
        }

        $course = ArrayToolkit::parts($course, array('title', 'type', 'about', 'categoryId'));
        $course['status'] = 'draft';
        $course['about'] = !empty($course['about']) ? $this->purifyHtml($course['about']) : '';
        $course['userId'] = $this->getCurrentUser()->id;
        $course['createdTime'] = time();
        $course['teacherIds'] = array($course['userId']);

        $course = $this->getOpenCourseDao()->create($course);

        $member = array(
            'courseId' => $course['id'],
            'userId' => $course['userId'],
            'role' => 'teacher',
        );

        $this->getOpenCourseMemberDao()->create($member);

        $this->getLogService()->info('open_course', 'create_course', "创建公开课《{$course['title']}》(#{$course['id']})");

        return $course;
    }

    protected function tryCreateCourse()
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin() || !($user->isTeacher() || $user->isAdmin() || $user->isSuperAdmin())) {
            throw $this->createAccessDeniedException('Unauthorized');
        }
    }

    public function updateCourse($id, $fields)
    {
        $user = $this->getCurrentUser();

        $argument = $fields;
        $course = $this->getCourse($id);

        if (empty($course)) {
            throw $this->createServiceException('课程不存在，更新失败！');
        }

        $fields = $this->_filterCourseFields($fields);

        $tagIds = empty($fields['tags']) ? array() : $fields['tags'];

        unset($fields['tags']);

        $this->getLogService()->info('open_course', 'update_course', "更新公开课《{$course['title']}》(#{$course['id']})的信息", $fields);

        $updatedCourse = $this->getOpenCourseDao()->update($id, $fields);

        $this->dispatchEvent('open.course.update', array('argument' => $argument, 'course' => $updatedCourse, 'tagIds' => $tagIds, 'userId' => $user['id']));

        return $updatedCourse;
    }

    public function deleteCourse($id)
    {
        $course = $this->tryAdminCourse($id);

        $this->getOpenCourseMemberDao()->deleteByCourseId($id);
        $this->deleteLessonsByCourseId($id);

        $this->getOpenCourseDao()->delete($id);

        if ($course['type'] == 'liveOpen') {
            $this->getLiveReplayService()->deleteReplaysByCourseId($id, 'liveOpen');
        }

        $this->getLogService()->info('open_course', 'delete_course', "删除公开课《{$course['title']}》(#{$course['id']})");

        $this->dispatchEvent('open.course.delete', $course);

        return true;
    }

    public function waveCourse($id, $field, $diff)
    {
        $this->getOpenCourseDao()->wave(array($id), array($field => $diff));

        return $this->getCourse($id);
    }

    public function publishCourse($id)
    {
        $course = $this->tryManageOpenCourse($id);

        $lessonCount = $this->countLessons(array('courseId' => $id, 'status' => 'published'));

        if ($lessonCount < 1) {
            return array('result' => false, 'message' => '请先添加课时并发布！');
        }

        $course = $this->updateCourse($id, array('status' => 'published'));
        $this->dispatchEvent('open.course.publish', $course);
        $this->getLogService()->info('open_course', 'pulish_course', "发布公开课《{$course['title']}》(#{$course['id']})");

        return array('result' => true, 'course' => $course);
    }

    public function closeCourse($id)
    {
        $course = $this->tryManageOpenCourse($id);

        if (empty($course)) {
            throw $this->createNotFoundException();
        }

        $this->getLogService()->info('open_course', 'close_course', "关闭公开课《{$course['title']}》(#{$course['id']})");
        $this->dispatchEvent('open.course.close', $course);

        return $this->getOpenCourseDao()->update($id, array('status' => 'closed'));
    }

    public function tryManageOpenCourse($courseId)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('未登录用户，无权操作！');
        }

        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException();
        }

        if (!$this->hasOpenCourseManagerRole($courseId, $user['id'])) {
            throw $this->createAccessDeniedException('您不是课程的教师或管理员，无权操作！');
        }

        return $course;
    }

    public function tryAdminCourse($courseId)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getCurrentUser();

        if (empty($user->id)) {
            throw $this->createAccessDeniedException('未登录用户，无权操作！');
        }

        if (count(array_intersect($user['roles'], array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'))) == 0) {
            throw $this->createAccessDeniedException('您不是管理员，无权操作！');
        }

        return $course;
    }

    public function changeCoursePicture($courseId, $data)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException('课程不存在，图标更新失败！');
        }

        $fileIds = ArrayToolkit::column($data, 'id');
        $files = $this->getFileService()->getFilesByIds($fileIds);

        $files = ArrayToolkit::index($files, 'id');
        $fileIds = ArrayToolkit::index($data, 'type');

        $fields = array(
            'smallPicture' => $files[$fileIds['small']['id']]['uri'],
            'middlePicture' => $files[$fileIds['middle']['id']]['uri'],
            'largePicture' => $files[$fileIds['large']['id']]['uri'],
        );

        $this->_deleteNotUsedPictures($course);

        $this->getLogService()->info('open_course', 'update_picture', "更新公开课《{$course['title']}》(#{$course['id']})图片", $fields);

        $update_picture = $this->getOpenCourseDao()->update($courseId, $fields);

        $this->dispatchEvent('open.course.picture.update', array('argument' => $data, 'course' => $update_picture));

        return $update_picture;
    }

    public function favoriteCourse($courseId)
    {
        $user = $this->getCurrentUser();

        if (empty($user['id'])) {
            throw $this->createAccessDeniedException();
        }

        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException('该课程不存在,收藏失败!');
        }

        if ($course['status'] != 'published') {
            throw $this->createServiceException('不能收藏未发布课程');
        }

        $favorite = $this->getFavoriteDao()->getByUserIdAndCourseId($user['id'], $course['id'], 'openCourse');

        if ($favorite) {
            throw $this->createServiceException('该收藏已经存在，请不要重复收藏!');
        }

        //添加动态
        $this->dispatchEvent(
            'open.course.favorite',
            new Event($course)
        );

        $this->getFavoriteDao()->create(array(
            'courseId' => $course['id'],
            'userId' => $user['id'],
            'createdTime' => time(),
            'type' => 'openCourse',
        ));

        $courseFavoriteNum = $this->getFavoriteDao()->count(array(
            'courseId' => $courseId,
            'type' => 'openCourse',
        ));

        return $courseFavoriteNum;
    }

    public function unFavoriteCourse($courseId)
    {
        $user = $this->getCurrentUser();

        if (empty($user['id'])) {
            throw $this->createAccessDeniedException();
        }

        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException('该课程不存在,收藏失败!');
        }

        $favorite = $this->getFavoriteDao()->getByUserIdAndCourseId($user['id'], $course['id'], 'openCourse');

        if (empty($favorite)) {
            throw $this->createServiceException('你未收藏本课程，取消收藏失败!');
        }

        $this->getFavoriteDao()->delete($favorite['id']);

        $courseFavoriteNum = $this->getFavoriteDao()->count(array(
            'courseId' => $courseId,
            'type' => 'openCourse',
        ));

        return $courseFavoriteNum;
    }

    public function getFavoriteByUserIdAndCourseId($userId, $courseId, $type)
    {
        return $this->getFavoriteDao()->getByUserIdAndCourseId($userId, $courseId, $type);
    }

    public function getLessonItems($courseId)
    {
        $lessons = $this->searchLessons(array('courseId' => $courseId), array('seq' => 'ASC'), 0, 1);

        $items = array();

        foreach ($lessons as $lesson) {
            $lesson['itemType'] = 'lesson';
            $items["lesson-{$lesson['id']}"] = $lesson;
        }

        uasort($items, function ($item1, $item2) {
            return $item1['seq'] > $item2['seq'];
        });

        return $items;
    }

    /**
     * open_course_lesson.
     */
    public function getLesson($id)
    {
        return $this->getOpenCourseLessonDao()->get($id);
    }

    public function findLessonsByIds(array $ids)
    {
        return $this->getOpenCourseLessonDao()->findByIds($ids);
    }

    public function findLessonsByCourseId($courseId)
    {
        return $this->getOpenCourseLessonDao()->findByCourseId($courseId);
    }

    public function searchLessons($condition, $orderBy, $start, $limit)
    {
        return $this->getOpenCourseLessonDao()->search($condition, $orderBy, $start, $limit);
    }

    public function countLessons($conditions)
    {
        return $this->getOpenCourseLessonDao()->count($conditions);
    }

    public function createLesson($lesson)
    {
        $lesson = ArrayToolkit::filter($lesson, array(
            'courseId' => 0,
            'chapterId' => 0,
            'seq' => 0,
            'free' => 0,
            'title' => '',
            'summary' => '',
            'type' => 'text',
            'media' => array(),
            'content' => '',
            'mediaId' => 0,
            'mediaName' => '',
            'mediaUri' => '',
            'length' => 0,
            'startTime' => 0,
            'giveCredit' => 0,
            'requireCredit' => 0,
            'liveProvider' => 'none',
            'copyId' => 0,
            'testMode' => 'normal',
            'testStartTime' => 0,
            'status' => 'unpublished',
            'mediaSource' => '',
        ));
        $lesson['replayStatus'] = 'ungenerated';

        if (!ArrayToolkit::requireds($lesson, array('courseId', 'title', 'type'))) {
            throw $this->createServiceException('参数缺失，创建课时失败！');
        }

        if (empty($lesson['courseId'])) {
            throw $this->createServiceException('添加课时失败，课程ID为空。');
        }

        $course = $this->getCourse($lesson['courseId'], true);

        if (empty($course)) {
            throw $this->createServiceException('添加课时失败，课程不存在。');
        }

        if (!in_array($lesson['type'], array('video', 'liveOpen', 'open'))) {
            throw $this->createServiceException('课时类型不正确，添加失败！');
        }

        $this->fillLessonMediaFields($lesson);

        if (isset($fields['title'])) {
            $fields['title'] = $this->purifyHtml($fields['title']);
        }

        $lesson['status'] = 'unpublished';
        $lesson['number'] = $this->_getNextLessonNumber($lesson['courseId']);
        $lesson['seq'] = $this->_getNextCourseItemSeq($lesson['courseId']);
        $lesson['userId'] = $this->getCurrentUser()->id;
        $lesson['createdTime'] = time();

        if ($lesson['type'] == 'liveOpen') {
            $lesson['endTime'] = $lesson['startTime'] + $lesson['length'] * 60;
        }

        $lesson = $this->getOpenCourseLessonDao()->create($lesson);

        if (!empty($lesson['mediaId'])) {
            $this->getUploadFileService()->waveUploadFile($lesson['mediaId'], 'usedCount', 1);
        }

        $this->getLogService()->info('open_course', 'add_lesson', "添加公开课时《{$lesson['title']}》({$lesson['id']})", $lesson);
        $this->dispatchEvent('open.course.lesson.create', array('lesson' => $lesson));

        return $lesson;
    }

    public function updateLesson($courseId, $lessonId, $fields)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException("课程(#{$courseId})不存在！");
        }

        $lesson = $this->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createServiceException("课时(#{$lessonId})不存在！");
        }

        $fields = ArrayToolkit::filter($fields, array(
            'title' => '',
            'summary' => '',
            'content' => '',
            'media' => array(),
            'mediaId' => 0,
            'mediaSource' => '',
            'mediaName' => '',
            'mediaUri' => '',
            'number' => 0,
            'seq' => 0,
            'chapterId' => 0,
            'free' => 0,
            'length' => 0,
            'startTime' => 0,
            'giveCredit' => 0,
            'requireCredit' => 0,
            'homeworkId' => 0,
            'exerciseId' => 0,
            'testMode' => 'normal',
            'testStartTime' => 0,
            'replayStatus' => 'ungenerated',
            'status' => 'unpublished',
            'materialNum' => 0,
        ));

        if (isset($fields['title'])) {
            $fields['title'] = $this->purifyHtml($fields['title']);
        }

        $fields['type'] = $lesson['type'];

        if ($fields['type'] == 'liveOpen' && isset($fields['startTime'])) {
            $fields['endTime'] = $fields['startTime'] + $fields['length'] * 60;
        }

        if (array_key_exists('media', $fields)) {
            $this->fillLessonMediaFields($fields);
        }

        $updatedLesson = $this->getOpenCourseLessonDao()->update($lessonId, $fields);

        $updatedLesson['fields'] = $lesson;
        $this->dispatchEvent('open.course.lesson.update', array('lesson' => $updatedLesson, 'sourceLesson' => $lesson));

        $this->getLogService()->info('open_course', 'update_lesson', "更新公开课时《{$updatedLesson['title']}》({$updatedLesson['id']})", $updatedLesson);

        return $updatedLesson;
    }

    public function waveCourseLesson($id, $field, $diff)
    {
        return $this->getOpenCourseLessonDao()->wave(array($id), array($field => $diff));
    }

    public function deleteLesson($id)
    {
        $lesson = $this->getLesson($id);

        $result = $this->getOpenCourseLessonDao()->delete($id);

        $this->dispatchEvent('open.course.lesson.delete', array('lesson' => $lesson));

        return $result;
    }

    public function generateLessonVideoReplay($courseId, $lessonId, $fileId)
    {
        $lesson = $this->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createServiceException("课时(#{$lessonId})不存在！");
        }

        $file = $this->getUploadFileService()->getFile($fileId);
        if (!$file) {
            throw $this->createServiceException('文件不存在');
        }

        $lessonFields = array(
            'mediaId' => $file['id'],
            'mediaName' => $file['filename'],
            'mediaSource' => 'self',
            'replayStatus' => 'videoGenerated',
        );

        $updatedLesson = $this->getOpenCourseLessonDao()->update($lessonId, $lessonFields);

        $this->dispatchEvent('open.course.lesson.generate.video.replay', array('lesson' => $updatedLesson));

        return $lesson;
    }

    public function getCourseLesson($courseId, $lessonId)
    {
        $lesson = $this->getOpenCourseLessonDao()->get($lessonId);

        if (empty($lesson) || ($lesson['courseId'] != $courseId)) {
            return null;
        }

        return $lesson;
    }

    public function getNextLesson($courseId, $lessonId)
    {
        $lesson = $this->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createNotFoundException(sprintf('lesson #%s not found', $lessonId));
        }

        $conditions = array(
            'number' => $lesson['number'] + 1,
            'courseId' => $courseId,
        );
        $orderBy = array('seq' => 'ASC');
        $nextLessons = $this->searchLessons($conditions, $orderBy, 0, 1);

        return array_shift($nextLessons);
    }

    public function publishLesson($courseId, $lessonId)
    {
        $course = $this->tryManageOpenCourse($courseId);

        $lesson = $this->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createServiceException("课时#{$lessonId}不存在");
        }

        $publishedLesson = $this->getOpenCourseLessonDao()->update($lesson['id'], array('status' => 'published'));

        $this->dispatchEvent('open.course.lesson.publish', $publishedLesson);

        return $publishedLesson;
    }

    public function unpublishLesson($courseId, $lessonId)
    {
        $course = $this->tryManageOpenCourse($courseId);

        $lesson = $this->getCourseLesson($course['id'], $lessonId);

        if (empty($lesson)) {
            throw $this->createServiceException("课时#{$lessonId}不存在");
        }

        $lesson = $this->getOpenCourseLessonDao()->update($lesson['id'], array('status' => 'unpublished'));

        $this->dispatchEvent('open.course.lesson.unpublish', array('lesson' => $lesson));

        return $lesson;
    }

    public function resetLessonMediaId($lessonId)
    {
        $lesson = $this->getLesson($lessonId);
        if ($lesson) {
            $this->getOpenCourseLessonDao()->update($lesson['id'], array('mediaId' => 0));

            return true;
        }

        return false;
    }

    public function sortCourseItems($courseId, array $itemIds)
    {
        $items = $this->getLessonItems($courseId);
        $existedItemIds = array_keys($items);

        if (count($itemIds) != count($existedItemIds)) {
            throw $this->createServiceException('itemdIds参数不正确');
        }

        $diffItemIds = array_diff($itemIds, array_keys($items));

        if (!empty($diffItemIds)) {
            throw $this->createServiceException('itemdIds参数不正确');
        }

        $lessonNum = $seq = 0;

        foreach ($itemIds as $itemId) {
            ++$seq;
            list($type) = explode('-', $itemId);
            ++$lessonNum;

            $item = $items[$itemId];
            $fields = array('number' => $lessonNum, 'seq' => $seq);

            if ($fields['number'] != $item['number'] || $fields['seq'] != $item['seq']) {
                $this->updateLesson($courseId, $item['id'], $fields);
            }
        }
    }

    public function liveLessonTimeCheck($courseId, $lessonId, $startTime, $length)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException('此课程不存在！');
        }

        $thisStartTime = $thisEndTime = 0;

        $conditions = array();

        if ($lessonId) {
            $liveLesson = $this->getCourseLesson($course['id'], $lessonId);
            $conditions['lessonIdNotIn'] = array($lessonId);
        } else {
            $lessonId = '';
        }

        $startTime = is_numeric($startTime) ? $startTime : strtotime($startTime);
        $endTime = $startTime + $length * 60;

        $conditions['courseId'] = $courseId;
        $conditions['startTimeGreaterThan'] = $startTime;
        $conditions['endTimeLessThan'] = $endTime;

        $thisLessons = $this->getOpenCourseLessonDao()->findTimeSlotOccupiedLessonsByCourseId($courseId, $startTime, $endTime, $lessonId);

        if (($length / 60) > 8) {
            return array('error_timeout', '时长不能超过8小时！');
        }

        if ($thisLessons) {
            return array('error_occupied', '该时段内已有直播课时存在，请调整直播开始时间');
        }

        return array('success', '');
    }

    /**
     * open_course_member.
     */
    public function getMember($id)
    {
        return $this->getOpenCourseMemberDao()->get($id);
    }

    public function getCourseMember($courseId, $userId)
    {
        return $this->getOpenCourseMemberDao()->getByUserIdAndCourseId($courseId, $userId);
    }

    public function getCourseMemberByIp($courseId, $ip)
    {
        return $this->getOpenCourseMemberDao()->getByIpAndCourseId($courseId, $ip);
    }

    public function getCourseMemberByMobile($courseId, $mobile)
    {
        return $this->getOpenCourseMemberDao()->getByMobileAndCourseId($courseId, $mobile);
    }

    public function findMembersByCourseIds($courseIds)
    {
        return $this->getOpenCourseMemberDao()->findByCourseIds($courseIds);
    }

    public function countMembers($conditions)
    {
        return $this->getOpenCourseMemberDao()->count($conditions);
    }

    public function searchMembers($conditions, $orderBy, $start, $limit)
    {
        return $this->getOpenCourseMemberDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function setCourseTeachers($courseId, $teachers)
    {
        $teacherMembers = array();

        foreach (array_values($teachers) as $index => $teacher) {
            if (empty($teacher['id'])) {
                throw $this->createServiceException("教师ID不能为空，设置课程(#{$courseId})教师失败");
            }

            $user = $this->getUserService()->getUser($teacher['id']);

            if (empty($user)) {
                throw $this->createServiceException("用户不存在或没有教师角色，设置课程(#{$courseId})教师失败");
            }

            $teacherMembers[] = array(
                'courseId' => $courseId,
                'userId' => $user['id'],
                'role' => 'teacher',
                'seq' => $index,
                'isVisible' => empty($teacher['isVisible']) ? 0 : 1,
                'createdTime' => time(),
            );
        }

        $existTeacherMembers = $this->findCourseTeachers($courseId);

        foreach ($existTeacherMembers as $member) {
            $this->getOpenCourseMemberDao()->delete($member['id']);
        }

        $visibleTeacherIds = array();

        foreach ($teacherMembers as $member) {
            $existMember = $this->getCourseMember($courseId, $member['userId']);

            if ($existMember) {
                $this->getOpenCourseMemberDao()->delete($existMember['id']);
            }

            $member = $this->getOpenCourseMemberDao()->create($member);

            if ($member['isVisible']) {
                $visibleTeacherIds[] = $member['userId'];
            }
        }

        $this->getLogService()->info('open_course', 'update_teacher', "更新课程#{$courseId}的教师", $teacherMembers);

        $fields = array('teacherIds' => $visibleTeacherIds);
        $course = $this->updateCourse($courseId, $fields);
    }

    public function createMember($member)
    {
        $user = $this->getCurrentUser();

        if ($user->isLogin()) {
            $member['userId'] = $user['id'];
            $member['mobile'] = isset($user['verifiedMobile']) ? $user['verifiedMobile'] : '';
        } else {
            $member['userId'] = 0;
        }

        $member['createdTime'] = time();

        $newMember = $this->getOpenCourseMemberDao()->create($member);

        $this->dispatchEvent('open.course.member.create', array('argument' => $member, 'newMember' => $newMember));

        return $newMember;
    }

    public function updateMember($id, $member)
    {
        $member = ArrayToolkit::filter($member, array(
            'userId' => 0,
            'learnedNum' => '',
            'learnTime' => '',
            'role' => '',
            'ip' => '',
            'lastEnterTime' => 0,
            'mobile' => '',
            'seq' => 0,
            'isVisible' => 1,
            'isNotified' => 0,
        ));

        return $this->getOpenCourseMemberDao()->update($id, $member);
    }

    public function deleteMember($id)
    {
        return $this->getOpenCourseMemberDao()->delete($id);
    }

    protected function deleteLessonsByCourseId($courseId)
    {
        $lessons = $this->findLessonsByCourseId($courseId);
        $this->getOpenCourseLessonDao()->deleteByCourseId($courseId);

        if ($lessons) {
            foreach ($lessons as $key => $lesson) {
                $this->dispatchEvent('open.course.lesson.delete', array('lesson' => $lesson));
            }
        }
    }

    protected function fillLessonMediaFields(&$lesson)
    {
        if ($lesson['type'] == 'liveOpen' && $lesson['replayStatus'] != 'videoGenerated') {
            $lesson['mediaName'] = '';
            $lesson['mediaSource'] = '';
            $lesson['mediaUri'] = '';
        } elseif ($lesson['mediaSource'] == 'self') {
            $file = $this->getUploadFileService()->getFile($lesson['mediaId']);
            $lesson['mediaName'] = $file['filename'];
            $lesson['mediaSource'] = 'self';
            $lesson['mediaUri'] = '';
        }
        unset($lesson['media']);

        return $lesson;
    }

    protected function _filterCourseFields($fields)
    {
        $fields = ArrayToolkit::filter($fields, array(
            'title' => '',
            'subtitle' => '',
            'about' => '',
            'categoryId' => 0,
            'startTime' => 0,
            'endTime' => 0,
            'tags' => '',
            'locationId' => 0,
            'address' => '',
            'locked' => 0,
            'hitNum' => 0,
            'likeNum' => 0,
            'postNum' => 0,
            'status' => 'draft',
            'lessonNum' => 0,
            'smallPicture' => '',
            'middlePicture' => '',
            'largePicture' => '',
            'teacherIds' => array(),
            'recommended' => 0,
            'recommendedSeq' => 0,
            'recommendedTime' => 0,
            'studentNum' => 0,
            'updateTime' => time(),
        ));

        if (isset($fields['tags'])) {
            if (!empty($fields['tags'])) {
                $fields['tags'] = explode(',', $fields['tags']);
                $fields['tags'] = $this->getTagService()->findTagsByNames($fields['tags']);
                array_walk($fields['tags'], function (&$item, $key) {
                    $item = (int) $item['id'];
                });
            } else {
                $fields['tags'] = array();
            }
        }

        if (isset($fields['about'])) {
            $fields['about'] = $this->purifyHtml($fields['about'], true);
        }

        return $fields;
    }

    protected function hasOpenCourseManagerRole($courseId, $userId)
    {
        if ($this->getUserService()->hasAdminRoles($userId)) {
            return true;
        }

        $member = $this->getCourseMember($courseId, $userId);

        if ($member && ($member['role'] == 'teacher')) {
            return true;
        }

        return false;
    }

    protected function _prepareCourseConditions($conditions)
    {
        $conditions = array_filter($conditions, function ($value) {
            if ($value == 0) {
                return true;
            }

            return !empty($value);
        });

        if (isset($conditions['creator']) && !empty($conditions['creator'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['creator']);
            $conditions['userId'] = $user ? $user['id'] : -1;
            unset($conditions['creator']);
        }

        if (isset($conditions['categoryId']) && !empty($conditions['categoryId'])) {
            $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
            $conditions['categoryIds'] = array_merge(array($conditions['categoryId']), $childrenIds);
            unset($conditions['categoryId']);
        }

        if (isset($conditions['nickname'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['nickname']);
            $conditions['userId'] = $user ? $user['id'] : -1;
            unset($conditions['nickname']);
        }

        return $conditions;
    }

    private function _getNextLessonNumber($courseId)
    {
        $lessonCount = $this->countLessons(array('courseId' => $courseId));

        return $lessonCount + 1;
    }

    private function _getNextCourseItemSeq($courseId)
    {
        $lessonMaxSeq = $this->getOpenCourseLessonDao()->getLessonMaxSeqByCourseId($courseId);

        return $lessonMaxSeq + 1;
    }

    private function _deleteNotUsedPictures($course)
    {
        $oldPictures = array(
            'smallPicture' => $course['smallPicture'] ? $course['smallPicture'] : null,
            'middlePicture' => $course['middlePicture'] ? $course['middlePicture'] : null,
            'largePicture' => $course['largePicture'] ? $course['largePicture'] : null,
        );

        $courseCount = $this->countCourses(array('smallPicture' => $course['smallPicture']));

        if ($courseCount <= 1) {
            $fileService = $this->getFileService();
            array_map(function ($oldPicture) use ($fileService) {
                if (!empty($oldPicture)) {
                    $fileService->deleteFileByUri($oldPicture);
                }
            }, $oldPictures);
        }
    }

    public function findCourseTeachers($courseId)
    {
        $conditions = array(
            'courseId' => $courseId,
            'role' => 'teacher',
        );
        $orders = array('seq' => 'DESC', 'createdTime' => 'DESC');

        return $this->getOpenCourseMemberDao()->search($conditions, $orders, 0, 100);
    }

    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getLiveReplayService()
    {
        return $this->createService('Course:LiveReplayService');
    }

    /**
     * @return OpenCourseDao
     */
    protected function getOpenCourseDao()
    {
        return $this->createDao('OpenCourse:OpenCourseDao');
    }

    /**
     * @return OpenCourseLessonDao
     */
    protected function getOpenCourseLessonDao()
    {
        return $this->createDao('OpenCourse:OpenCourseLessonDao');
    }

    /**
     * @return OpenCourseMemberDao
     */
    protected function getOpenCourseMemberDao()
    {
        return $this->createDao('OpenCourse:OpenCourseMemberDao');
    }

    /**
     * @return FavoriteDao
     */
    protected function getFavoriteDao()
    {
        return $this->createDao('Course:FavoriteDao');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    protected function getTagService()
    {
        return $this->createService('Taxonomy:TagService');
    }

    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }
}
