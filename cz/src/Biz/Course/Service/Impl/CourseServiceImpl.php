<?php

namespace Biz\Course\Service\Impl;

use Biz\Accessor\AccessorInterface;
use Biz\BaseService;
use Biz\Course\Dao\CourseDao;
use Biz\Course\Dao\ThreadDao;
use Biz\Course\Dao\FavoriteDao;
use Biz\Course\Dao\CourseSetDao;
use Biz\Exception\UnableJoinException;
use Biz\Task\Service\TaskService;
use Biz\Task\Strategy\CourseStrategy;
use Biz\Task\Visitor\CourseItemPagingVisitor;
use Biz\Task\Visitor\CourseItemSortingVisitor;
use Biz\User\Service\UserService;
use Biz\System\Service\LogService;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Dao\CourseMemberDao;
use Biz\Course\Dao\CourseChapterDao;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\Course\Service\ReviewService;
use Biz\System\Service\SettingService;
use Biz\Course\Service\MaterialService;
use Biz\Task\Service\TaskResultService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\CourseNoteService;
use Biz\Taxonomy\Service\CategoryService;
use Biz\Classroom\Service\ClassroomService;
use Biz\Course\Service\CourseDeleteService;
use Biz\Activity\Service\Impl\ActivityServiceImpl;

class CourseServiceImpl extends BaseService implements CourseService
{
    const MAX_REWARD_POINT = 100000;

    public function getCourse($id)
    {
        return $this->getCourseDao()->get($id);
    }

    public function findCoursesByIds($ids)
    {
        $courses = $this->getCourseDao()->findCoursesByIds($ids);

        return ArrayToolkit::index($courses, 'id');
    }

    public function findCoursesByCourseSetIds(array $setIds)
    {
        return $this->getCourseDao()->findByCourseSetIds($setIds);
    }

    public function findCoursesByCourseSetId($courseSetId)
    {
        return $this->getCourseDao()->findCoursesByCourseSetIdAndStatus($courseSetId, null);
    }

    public function findCoursesByParentIdAndLocked($parentId, $locked)
    {
        return $this->getCourseDao()->findCoursesByParentIdAndLocked($parentId, $locked);
    }

    public function findPublishedCoursesByCourseSetId($courseSetId)
    {
        return $this->getCourseDao()->findCoursesByCourseSetIdAndStatus($courseSetId, 'published');
    }

    public function getDefaultCourseByCourseSetId($courseSetId)
    {
        return $this->getCourseDao()->getDefaultCourseByCourseSetId($courseSetId);
    }

    public function getDefaultCoursesByCourseSetIds($courseSetIds)
    {
        return $this->getCourseDao()->getDefaultCoursesByCourseSetIds($courseSetIds);
    }

    public function getFirstPublishedCourseByCourseSetId($courseSetId)
    {
        $courses = $this->searchCourses(
            array(
                'courseSetId' => $courseSetId,
                'status' => 'published',
            ),
            array('createdTime' => 'ASC'),
            0,
            1
        );

        return array_shift($courses);
    }

    public function getFirstCourseByCourseSetId($courseSetId)
    {
        $courses = $this->searchCourses(
            array(
                'courseSetId' => $courseSetId,
            ),
            array('createdTime' => 'ASC'),
            0,
            1
        );

        return array_shift($courses);
    }

    public function createCourse($course)
    {
        if (!ArrayToolkit::requireds($course, array('title', 'courseSetId', 'expiryMode', 'learnMode'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }
        if (!in_array($course['learnMode'], static::learnModes())) {
            throw $this->createInvalidArgumentException('Param Invalid: LearnMode');
        }

        if (!in_array($course['courseType'], static::courseTypes())) {
            throw $this->createInvalidArgumentException('Param Invalid: CourseType');
        }

        if (!isset($course['isDefault'])) {
            $course['isDefault'] = 0;
        }

        $course = ArrayToolkit::parts(
            $course,
            array(
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
                'isFree',
                'serializeMode',
                'courseType',
                'type',
            )
        );

        if (isset($fields['about'])) {
            $fields['about'] = $this->purifyHtml($fields['about'], true);
        }

        if (!isset($course['isFree'])) {
            $course['isFree'] = 1; //默认免费
        }

        $course = $this->validateExpiryMode($course);

        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $course['maxRate'] = $courseSet['maxRate'];

        $course['status'] = 'draft';
        $course['creator'] = $this->getCurrentUser()->getId();
        try {
            $this->beginTransaction();

            $created = $this->getCourseDao()->create($course);
            $currentUser = $this->getCurrentUser();
            //set default teacher
            $this->getMemberService()->setCourseTeachers(
                $created['id'],
                array(
                    array(
                        'id' => $currentUser['id'],
                        'isVisible' => 1,
                    ),
                )
            );
            $this->commit();
            $this->dispatchEvent('course.create', new Event($created));

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function copyCourse($newCourse)
    {
        $sourceCourse = $this->tryManageCourse($newCourse['copyCourseId']);
        $newCourse = ArrayToolkit::parts(
            $newCourse,
            array(
                'title',
                'courseSetId',
                'learnMode',
                'expiryMode',
                'expiryDays',
                'expiryStartDate',
                'expiryEndDate',
                'courseType',
            )
        );

        $newCourse = $this->validateExpiryMode($newCourse);

        // $entityCopy = new CourseCopy($this->biz);

        // return $entityCopy->copy($sourceCourse, $newCourse);

        return $this->biz['course_copy']->copy($sourceCourse, $newCourse);
    }

    public function updateCourse($id, $fields)
    {
        $this->tryManageCourse($id);

        if (!ArrayToolkit::requireds($fields, array('title', 'courseSetId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'title',
                'courseSetId',
                'summary',
                'goals',
                'audiences',
                'enableFinish',
                'serializeMode',
                'maxStudentNum',
                'locked',
            )
        );

        if (isset($fields['about'])) {
            $fields['about'] = $this->purifyHtml($fields['about'], true);
        }

        if (isset($fields['summary'])) {
            $fields['summary'] = $this->purifyHtml($fields['summary'], true);
        }

        $course = $this->getCourseDao()->update($id, $fields);
        $this->dispatchEvent('course.update', new Event($course));

        return $course;
    }

    public function recommendCourseByCourseSetId($courseSetId, $fields)
    {
        $requiredKeys = array('recommended', 'recommendedSeq', 'recommendedTime');
        $fields = ArrayToolkit::parts($fields, $requiredKeys);
        if (!ArrayToolkit::requireds($fields, array('recommended', 'recommendedSeq', 'recommendedTime'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }
        $this->getCourseDao()->updateCourseRecommendByCourseSetId($courseSetId, $fields);
    }

    public function cancelRecommendCourseByCourseSetId($courseSetId)
    {
        $fields = array(
            'recommended' => 0,
            'recommendedTime' => 0,
            'recommendedSeq' => 0,
        );
        $this->getCourseDao()->updateCourseRecommendByCourseSetId($courseSetId, $fields);
    }

    public function updateMaxRate($id, $maxRate)
    {
        $course = $this->getCourseDao()->update($id, array('maxRate' => $maxRate));
        $this->dispatchEvent('course.update', new Event($course));

        return $course;
    }

    public function updateCategoryByCourseSetId($courseSetId, $categoryId)
    {
        $this->getCourseDao()->updateCategoryByCourseSetId($courseSetId, array('categoryId' => $categoryId));
    }

    public function updateMaxRateByCourseSetId($courseSetId, $maxRate)
    {
        $course = $this->getCourseDao()->updateMaxRateByCourseSetId(
            $courseSetId,
            array('updatedTime' => time(), 'maxRate' => $maxRate)
        );

        return $course;
    }

    public function updateCourseMarketing($id, $fields)
    {
        $oldCourse = $this->tryManageCourse($id);

        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'isFree',
                'originPrice',
                'vipLevelId',
                'buyable',
                'tryLookable',
                'tryLookLength',
                'watchLimit',
                'buyExpiryTime',
                'showServices',
                'services',
                'approval',
                'coinPrice',
                'expiryMode', //days、end_date、date、forever
                'expiryDays',
                'expiryStartDate',
                'expiryEndDate',
                'taskRewardPoint',
                'rewardPoint',
            )
        );

        $requireFields = array('isFree', 'buyable');
        $courseSet = $this->getCourseSetService()->getCourseSet($oldCourse['courseSetId']);

        if ($courseSet['status'] == 'published') {
            //课程发布不允许修改模式和时间
            unset($fields['expiryMode']);
            unset($fields['expiryDays']);
            unset($fields['expiryStartDate']);
            unset($fields['expiryEndDate']);
        } else {
            $fields['expiryMode'] = isset($fields['expiryMode']) ? $fields['expiryMode'] : $oldCourse['expiryMode'];
        }

        if (!$this->isTeacherAllowToSetRewardPoint()) {
            unset($fields['taskRewardPoint']);
            unset($fields['rewardPoint']);
        }

        $requireFields = array('isFree', 'buyable');
        $courseSet = $this->getCourseSetService()->getCourseSet($oldCourse['courseSetId']);

        if ($courseSet['type'] == 'normal' && $this->isCloudStorage()) {
            array_push($requireFields, 'tryLookable');
        } else {
            $fields['tryLookable'] = 0;
        }

        if (!ArrayToolkit::requireds($fields, $requireFields)) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = $this->validateExpiryMode($fields);

        if ($oldCourse['status'] == 'published' || $oldCourse['status'] == 'closed') {
            //课程计划发布或者关闭，不允许修改模式，但是允许修改时间
            unset($fields['expiryMode']);

            if ($courseSet['status'] == 'published') {
                //课程计划发布或者关闭，课程也发布，不允许修改时间
                unset($fields['expiryDays']);
                unset($fields['expiryStartDate']);
                unset($fields['expiryEndDate']);
            }
        }

        $fields = $this->processFields($id, $fields, $courseSet);

        $newCourse = $this->getCourseDao()->update($id, $fields);

        $this->dispatchEvent('course.update', new Event($newCourse));
        $this->dispatchEvent('course.marketing.update', array('oldCourse' => $oldCourse, 'newCourse' => $newCourse));

        return $newCourse;
    }

    public function updateCourseRewardPoint($id, $fields)
    {
        $oldCourse = $this->tryManageCourse($id);

        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'taskRewardPoint',
                'rewardPoint',
            )
        );

        $newCourse = $this->getCourseDao()->update($id, $fields);

        $this->dispatchEvent('course.update', new Event($newCourse));
        $this->dispatchEvent('course.reward_point.update', array('oldCourse' => $oldCourse, 'newCourse' => $newCourse));

        return $newCourse;
    }

    public function validateCourseRewardPoint($fields)
    {
        $result = false;

        if (isset($fields['taskRewardPoint'])) {
            if ((!preg_match('/^\+?[0-9][0-9]*$/', $fields['taskRewardPoint'])) || ($fields['taskRewardPoint'] > self::MAX_REWARD_POINT)) {
                $result = true;
            }
        }

        if (isset($fields['rewardPoint'])) {
            if ((!preg_match('/^\+?[0-9][0-9]*$/', $fields['rewardPoint'])) || ($fields['rewardPoint'] > self::MAX_REWARD_POINT)) {
                $result = true;
            }
        }

        return $result;
    }

    protected function isTeacherAllowToSetRewardPoint()
    {
        $rewardPointSetting = $this->getSettingService()->get('reward_point', array());

        return !empty($rewardPointSetting) && $rewardPointSetting['enable'] && $rewardPointSetting['allowTeacherSet'];
    }

    protected function isCloudStorage()
    {
        $storage = $this->getSettingService()->get('storage', array());

        return !empty($storage['upload_mode']) && $storage['upload_mode'] === 'cloud';
    }

    /**
     * 计算教学计划价格和虚拟币价格
     *
     * @param  $id
     * @param int|float $originPrice 教学计划原价
     *
     * @return array (number, number)
     */
    protected function calculateCoursePrice($id, $originPrice)
    {
        $course = $this->getCourse($id);
        $price = $originPrice;
        $coinPrice = $course['originCoinPrice'];
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        if (!empty($courseSet['discountId'])) {
            $price = $price * $courseSet['discount'] / 10;
            $coinPrice = $coinPrice * $courseSet['discount'] / 10;
        }

        return array($price, $coinPrice);
    }

    public function updateCourseStatistics($id, $fields)
    {
        if (empty($fields)) {
            throw $this->createInvalidArgumentException('Invalid Arguments');
        }

        $updateFields = array();
        foreach ($fields as $field) {
            if ($field === 'studentNum') {
                $updateFields['studentNum'] = $this->countStudentsByCourseId($id);
            } elseif ($field === 'taskNum') {
                $updateFields['taskNum'] = $this->getTaskService()->countTasks(
                    array('courseId' => $id, 'isOptional' => 0)
                );
            } elseif ($field === 'compulsoryTaskNum') {
                $updateFields['compulsoryTaskNum'] = $this->getTaskService()->countTasks(
                    array('courseId' => $id, 'isOptional' => 0)
                );
            } elseif ($field === 'threadNum') {
                $updateFields['threadNum'] = $this->countThreadsByCourseId($id);
            } elseif ($field === 'ratingNum') {
                $ratingFields = $this->getReviewService()->countRatingByCourseId($id);
                $updateFields = array_merge($updateFields, $ratingFields);
            } elseif ($field === 'noteNum') {
                $updateFields['noteNum'] = $this->getNoteService()->countCourseNoteByCourseId($id);
            } elseif ($field === 'materialNum') {
                $updateFields['materialNum'] = $this->getCourseMaterialService()->countMaterials(
                    array('courseId' => $id, 'source' => 'coursematerial')
                );
            }
        }

        if (empty($updateFields)) {
            throw $this->createInvalidArgumentException('Invalid Arguments');
        }

        $course = $this->getCourseDao()->update($id, $updateFields);
        $this->dispatchEvent('course.update', new Event($course));

        return $course;
    }

    public function deleteCourse($id)
    {
        $course = $this->tryManageCourse($id);
        if ($course['status'] == 'published') {
            throw $this->createAccessDeniedException('Deleting published Course is not allowed');
        }

        $subCourses = $this->findCoursesByParentIdAndLocked($id, 1);
        if (!empty($subCourses)) {
            throw $this->createAccessDeniedException('该教学计划被班级引用，请先移除班级计划');
        }
        $courseCount = $this->countCourses(array('courseSetId' => $course['courseSetId']));
        if ($courseCount <= 1) {
            throw $this->createAccessDeniedException('课程下至少需保留一个教学计划');
        }

        $result = $this->getCourseDeleteService()->deleteCourse($id);

        $this->dispatchEvent('course.delete', new Event($course));

        $this->getCourseDao()->delete($id);

        return $result;
    }

    public function closeCourse($id)
    {
        $course = $this->tryManageCourse($id);
        if ($course['status'] != 'published') {
            throw $this->createAccessDeniedException('Course has not bean published');
        }
        $course['status'] = 'closed';

        try {
            $this->beginTransaction();
            $course = $this->getCourseDao()->update($id, $course);

            $publishedCourses = $this->findPublishedCoursesByCourseSetId($course['courseSetId']);
            //如果课程下没有了已发布的教学计划，则关闭此课程
            if (empty($publishedCourses)) {
                $this->getCourseSetDao()->update($course['courseSetId'], array('status' => 'closed'));
            }
            $this->commit();
            $this->dispatchEvent('course.close', new Event($course));
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function publishCourse($id, $withTasks = false)
    {
        $this->tryManageCourse($id);
        $course = $this->getCourseDao()->update(
            $id,
            array(
                'status' => 'published',
            )
        );
        $this->dispatchEvent('course.publish', $course);

        $this->getTaskService()->publishTasksByCourseId($id);
    }

    protected function validateExpiryMode($course)
    {
        if (empty($course['expiryMode'])) {
            return $course;
        }
        //enum: [days,end_date,date,forever]
        if ($course['expiryMode'] === 'days') {
            $course['expiryStartDate'] = null;
            $course['expiryEndDate'] = null;

            if (empty($course['expiryDays'])) {
                throw $this->createInvalidArgumentException('Param Invalid: expiryDays');
            }
        } elseif ($course['expiryMode'] == 'end_date') {
            $course['expiryStartDate'] = null;
            $course['expiryDays'] = 0;

            if (empty($course['expiryEndDate'])) {
                throw $this->createInvalidArgumentException('Param Invalid: expiryEndDate');
            }
            $course['expiryEndDate'] = strtotime($course['expiryEndDate'].' 23:59:59');
        } elseif ($course['expiryMode'] === 'date') {
            $course['expiryDays'] = 0;
            if (isset($course['expiryStartDate'])) {
                $course['expiryStartDate'] = strtotime($course['expiryStartDate']);
            } else {
                throw $this->createInvalidArgumentException('Param Required: expiryStartDate');
            }
            if (empty($course['expiryEndDate'])) {
                throw $this->createInvalidArgumentException('Param Required: expiryEndDate');
            } else {
                $course['expiryEndDate'] = strtotime($course['expiryEndDate'].' 23:59:59');
            }
            if ($course['expiryEndDate'] <= $course['expiryStartDate']) {
                throw $this->createInvalidArgumentException(
                    'Value of Params expiryEndDate must later than expiryStartDate'
                );
            }
        } elseif ($course['expiryMode'] == 'forever') {
            $course['expiryStartDate'] = 0;
            $course['expiryEndDate'] = 0;
            $course['expiryDays'] = 0;
        } else {
            throw $this->createInvalidArgumentException('Param Invalid: expiryMode');
        }

        return $course;
    }

    public function findCourseItems($courseId, $limitNum = 0)
    {
        $course = $this->getCourse($courseId);
        if (empty($course)) {
            throw $this->createNotFoundException("Course#{$courseId} Not Found");
        }
        $tasks = $this->findTasksByCourseId($course);

        return $this->createCourseStrategy($course)->prepareCourseItems($courseId, $tasks, $limitNum);
    }

    protected function findTasksByCourseId($course)
    {
        $user = $this->getCurrentUser();
        if ($user->isLogin()) {
            return $this->getTaskService()->findTasksFetchActivityAndResultByCourseId($course['id']);
        }

        return $this->getTaskService()->findTasksFetchActivityByCourseId($course['id']);
    }

    public function findCourseItemsByPaging($courseId, $paging = array())
    {
        $course = $this->getCourse($courseId);
        if (empty($course)) {
            throw $this->createNotFoundException("Course#{$courseId} Not Found");
        }

        return $this->createCourseStrategy($course)->accept(new CourseItemPagingVisitor($this->biz, $courseId, $paging));
    }

    public function tryManageCourse($courseId, $courseSetId = 0)
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

        if (!$this->hasCourseManagerRole($courseId)) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        return $course;
    }

    public function findStudentsByCourseId($courseId)
    {
        $students = $this->getMemberDao()->findByCourseIdAndRole($courseId, 'student');

        return $this->fillMembersWithUserInfo($students);
    }

    public function findTeachersByCourseId($courseId)
    {
        $teachers = $this->getMemberDao()->findByCourseIdAndRole($courseId, 'teacher');

        return $this->fillMembersWithUserInfo($teachers);
    }

    public function countStudentsByCourseId($courseId)
    {
        return $this->getMemberDao()->count(
            array(
                'courseId' => $courseId,
                'role' => 'student',
            )
        );
    }

    // Refactor: 该函数不属于CourseService
    public function countThreadsByCourseId($courseId)
    {
        return $this->getThreadDao()->count(
            array(
                'courseId' => $courseId,
            )
        );
    }

    public function getUserRoleInCourse($courseId, $userId)
    {
        $member = $this->getMemberDao()->getByCourseIdAndUserId($courseId, $userId);

        return empty($member) ? null : $member['role'];
    }

    // Refactor: findTeachingCoursesByCourseSetId
    public function findUserTeachingCoursesByCourseSetId($courseSetId, $onlyPublished = true)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }

        $members = $this->getMemberService()->findTeacherMembersByUserIdAndCourseSetId($user['id'], $courseSetId);
        $ids = ArrayToolkit::column($members, 'courseId');
        if ($onlyPublished) {
            return $this->findPublicCoursesByIds($ids);
        } else {
            return $this->findCoursesByIds($ids);
        }
    }

    public function findPriceIntervalByCourseSetIds($courseSetIds)
    {
        $results = $this->getCourseDao()->findPriceIntervalByCourseSetIds($courseSetIds);

        return ArrayToolkit::index($results, 'courseSetId');
    }

    public function tryTakeCourse($courseId)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException("Course#{$courseId} Not Found");
        }
        if (!$this->canTakeCourse($course)) {
            throw $this->createAccessDeniedException("You have no access to the course#{$courseId} before you buy it");
        }
        $user = $this->getCurrentUser();
        $member = $this->getMemberDao()->getByCourseIdAndUserId($course['id'], $user['id']);

        return array($course, $member);
    }

    public function canTakeCourse($course)
    {
        $course = !is_array($course) ? $this->getCourse(intval($course)) : $course;

        if (empty($course)) {
            return false;
        }

        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            return false;
        }

        $member = $this->getMemberDao()->getByCourseIdAndUserId($course['id'], $user['id']);

        if ($member && in_array($member['role'], array('teacher', 'student'))) {
            return true;
        }

        if ($user->hasPermission('admin_course_manage')) {
            return true;
        }

        return false;
    }

    public function canJoinCourse($id)
    {
        $course = $this->getCourse($id);
        $chain = $this->biz['course.join_chain'];

        if (empty($chain)) {
            throw $this->createServiceException('Chain Not Registered');
        }

        return $chain->process($course);
    }

    public function canLearnCourse($id)
    {
        $course = $this->getCourse($id);
        $chain = $this->biz['course.learn_chain'];

        if (empty($chain)) {
            throw $this->createServiceException('Chain Not Registered');
        }

        return $chain->process($course);
    }

    public function canLearnTask($taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $chain = $this->biz['course.task.learn_chain'];

        if (empty($chain)) {
            throw $this->createServiceException('Chain Not Registered');
        }

        return $chain->process($task);
    }

    public function sortCourseItems($courseId, $ids)
    {
        $course = $this->tryManageCourse($courseId);
        try {
            $this->beginTransaction();
            $this->createCourseStrategy($course)->accept(new CourseItemSortingVisitor($this->biz, $courseId, $ids));
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function createChapter($chapter)
    {
        if (!in_array($chapter['type'], array('chapter', 'unit', 'lesson'))) {
            throw $this->createInvalidArgumentException('Invalid Chapter Type');
        }

        $chapter = $this->getChapterDao()->create($chapter);

        $this->dispatchEvent('course.chapter.create', new Event($chapter));

        return $chapter;
    }

    public function updateChapter($courseId, $chapterId, $fields)
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

    public function findChaptersByCourseId($courseId)
    {
        return $this->getChapterDao()->findChaptersByCourseId($courseId);
    }

    public function deleteChapter($courseId, $chapterId)
    {
        $this->tryManageCourse($courseId);

        $deletedChapter = $this->getChapterDao()->get($chapterId);

        if (empty($deletedChapter) || $deletedChapter['courseId'] != $courseId) {
            throw $this->createNotFoundException("Chapter#{$chapterId} Not Found");
        }

        $this->getChapterDao()->delete($deletedChapter['id']);

        $this->dispatchEvent('course.chapter.delete', new Event($deletedChapter));

        $this->getLogService()->info('course', 'delete_chapter', "删除章节(#{$chapterId})", $deletedChapter);
    }

    public function getChapter($courseId, $chapterId)
    {
        $chapter = $this->getChapterDao()->get($chapterId);
        $course = $this->getCourseDao()->get($courseId);
        if ($course['id'] == $chapter['courseId']) {
            return $chapter;
        }

        return array();
    }

    public function countUserLearningCourses($userId, $filters = array())
    {
        $conditions = $this->prepareUserLearnCondition($userId, $filters);

        return $this->getMemberDao()->countLearningMembers($conditions);
    }

    public function findUserLearningCourses($userId, $start, $limit, $filters = array())
    {
        $conditions = $this->prepareUserLearnCondition($userId, $filters);

        $members = $this->getMemberDao()->findLearningMembers($conditions, $start, $limit);

        $courses = $this->findCoursesByIds(ArrayToolkit::column($members, 'courseId'));
        $courses = ArrayToolkit::index($courses, 'id');

        $sortedCourses = array();

        foreach ($members as $member) {
            if (empty($courses[$member['courseId']])) {
                continue;
            }

            $course = $courses[$member['courseId']];
            $course['memberIsLearned'] = 0;
            $course['memberLearnedNum'] = $member['learnedNum'];
            $sortedCourses[] = $course;
        }

        return $sortedCourses;
    }

    // Refactor: countLearnedCourses
    public function countUserLearnedCourses($userId, $filters = array())
    {
        $conditions = $this->prepareUserLearnCondition($userId, $filters);

        return $this->getMemberDao()->countLearnedMembers($conditions);
    }

    // Refactor: findLearnedCourses
    public function findUserLearnedCourses($userId, $start, $limit, $filters = array())
    {
        $conditions = $this->prepareUserLearnCondition($userId, $filters);
        $members = $this->getMemberDao()->findLearnedMembers($conditions, $start, $limit);

        $courses = $this->findCoursesByIds(ArrayToolkit::column($members, 'courseId'));
        $courses = ArrayToolkit::index($courses, 'id');

        $sortedCourses = array();

        foreach ($members as $member) {
            if (empty($courses[$member['courseId']])) {
                continue;
            }

            $course = $courses[$member['courseId']];
            $course['memberIsLearned'] = 1;
            $course['memberLearnedNum'] = $member['learnedNum'];
            $sortedCourses[] = $course;
        }

        return $sortedCourses;
    }

    // Refactor: countTeachingCourses
    // 1、看是否应该改成：countTeachingCourseByUserId($userId, $onlyPublished = true)
    // 2、若参数列表保持原有，则需要校验必填参数conditions中是否包含userId
    public function findUserTeachCourseCount($conditions, $onlyPublished = true)
    {
        $members = $this->getMemberDao()->findByUserIdAndRole($conditions['userId'], 'teacher');
        unset($conditions['userId']);

        if (!$members) {
            return 0;
        }

        $conditions['courseIds'] = ArrayToolkit::column($members, 'courseId');

        if ($onlyPublished) {
            $conditions['status'] = 'published';
        }

        return $this->searchCourseCount($conditions);
    }

    // Refactor: findTeachingCoursesByUserId
    // 1、看是否应该改成：findTeachingCoursesByUserId($userId, $onlyPublished = true)
    // 2、若参数列表保持原有，则需要校验必填参数conditions中是否包含userId
    public function findUserTeachCourses($conditions, $start, $limit, $onlyPublished = true)
    {
        $members = $this->getMemberDao()->findByUserIdAndRole($conditions['userId'], 'teacher');
        unset($conditions['userId']);

        if (!$members) {
            return array();
        }

        $conditions['courseIds'] = ArrayToolkit::column($members, 'courseId');

        if ($onlyPublished) {
            $conditions['status'] = 'published';
        }

        return $this->searchCourses($conditions, array('createdTime' => 'DESC'), $start, $limit);
    }

    public function findLearnedCoursesByCourseIdAndUserId($courseId, $userId)
    {
        return $this->getMemberDao()->findLearnedByCourseIdAndUserId($courseId, $userId);
    }

    public function findTeachingCoursesByUserId($userId, $onlyPublished = true)
    {
        $members = $this->getMemberService()->findTeacherMembersByUserId($userId);
        $courseIds = ArrayToolkit::column($members, 'courseId');
        if ($onlyPublished) {
            $courses = $this->findPublicCoursesByIds($courseIds);
        } else {
            $courses = $this->findCoursesByIds($courseIds);
        }

        return $courses;
    }

    /**
     * @param int $userId
     *
     * @return mixed
     */
    public function findLearnCoursesByUserId($userId)
    {
        $members = $this->getMemberService()->findStudentMemberByUserId($userId);
        $courseIds = ArrayToolkit::column($members, 'courseId');
        $courses = $this->findPublicCoursesByIds($courseIds);

        return $courses;
    }

    public function findPublicCoursesByIds(array $ids)
    {
        if (empty($ids)) {
            return array();
        }

        $conditions = array(
            'status' => 'published',
            'courseIds' => $ids,
        );
        $count = $this->searchCourseCount($conditions);

        return $this->searchCourses($conditions, array('createdTime' => 'DESC'), 0, $count);
    }

    public function hasCourseManagerRole($courseId = 0)
    {
        $user = $this->getCurrentUser();
        //未登录，无权限管理
        if (!$user->isLogin()) {
            return false;
        }

        //不是管理员，无权限管理
        if ($this->hasAdminRole()) {
            return true;
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

    // Refactor: 函数命名
    public function analysisCourseDataByTime($startTime, $endTime)
    {
        return $this->getCourseDao()->analysisCourseDataByTime($startTime, $endTime);
    }

    public function findUserManageCoursesByCourseSetId($userId, $courseSetId)
    {
        $user = $this->getUserService()->getUser($userId);

        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user['roles']);
        $isAdmin = in_array('ROLE_ADMIN', $user['roles']);

        $courses = array();
        if ($isSuperAdmin || $isAdmin) {
            $courses = $this->findCoursesByCourseSetId($courseSetId);
        } elseif (in_array('ROLE_TEACHER', $user['roles'])) {
            $courses = $this->findUserTeachingCoursesByCourseSetId($courseSetId, false);
        }

        return $courses ? ArrayToolkit::index($courses, 'id') : array();
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
            $member['smallAvatar'] = $userMap[$member['userId']]['smallAvatar'];
            $members[$index] = $member;
        }

        return $members;
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
                'yesterday' => array(
                    strtotime('yesterday'),
                    strtotime('today'),
                ),
                'today' => array(
                    strtotime('today'),
                    strtotime('tomorrow'),
                ),
                'this_week' => array(
                    strtotime('Monday this week'),
                    strtotime('Monday next week'),
                ),
                'last_week' => array(
                    strtotime('Monday last week'),
                    strtotime('Monday this week'),
                ),
                'next_week' => array(
                    strtotime('Monday next week'),
                    strtotime('Monday next week', strtotime('Monday next week')),
                ),
                'this_month' => array(
                    strtotime('first day of this month midnight'),
                    strtotime('first day of next month midnight'),
                ),
                'last_month' => array(
                    strtotime('first day of last month midnight'),
                    strtotime('first day of this month midnight'),
                ),
                'next_month' => array(
                    strtotime('first day of next month midnight'),
                    strtotime('first day of next month midnight', strtotime('first day of next month midnight')),
                ),
            );

            if (array_key_exists($conditions['date'], $dates)) {
                $conditions['startTimeGreaterThan'] = $dates[$conditions['date']][0];
                $conditions['startTimeLessThan'] = $dates[$conditions['date']][1];
                unset($conditions['date']);
            }
        }

        if (isset($conditions['creator']) && !empty($conditions['creator'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['creator']);
            $conditions['userId'] = $user ? $user['id'] : -1;
            unset($conditions['creator']);
        }

        if (isset($conditions['categoryId'])) {
            $conditions['categoryIds'] = array();

            if (!empty($conditions['categoryId'])) {
                $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
                $conditions['categoryIds'] = array_merge(array($conditions['categoryId']), $childrenIds);
            }

            unset($conditions['categoryId']);
        }

        if (isset($conditions['nickname'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['nickname']);
            $conditions['userId'] = $user ? $user['id'] : -1;
            unset($conditions['nickname']);
        }

        return $conditions;
    }

    public function searchCourses($conditions, $sort, $start, $limit)
    {
        $conditions = $this->_prepareCourseConditions($conditions);
        $orderBy = $this->_prepareCourseOrderBy($sort);

        return $this->getCourseDao()->search($conditions, $orderBy, $start, $limit);
    }

    // Refactor: 该函数是否和getMinPublishedCoursePriceByCourseSetId冲突
    public function getMinAndMaxPublishedCoursePriceByCourseSetId($courseSetId)
    {
        return $this->getCourseDao()->getMinAndMaxPublishedCoursePriceByCourseSetId($courseSetId);
    }

    //移动端接口使用
    public function findCourseTasksAndChapters($courseId)
    {
        $course = $this->getCourse($courseId);
        $tasks = $this->getTaskService()->findTasksByCourseId($courseId);
        $items = $this->convertTasks($tasks, $course);

        $chapters = $this->getChapterDao()->findChaptersByCourseId($courseId);

        foreach ($chapters as $chapter) {
            if ($chapter['type'] == 'lesson') {
                continue;
            }
            $chapter['itemType'] = 'chapter';
            $items[] = $chapter;
        }
        uasort(
            $items,
            function ($item1, $item2) {
                return $item1['seq'] > $item2['seq'];
            }
        );

        return $items;
    }

    //移动端接口使用　task 转成lesson
    public function convertTasks($tasks, $course)
    {
        if (empty($tasks)) {
            return array();
        }

        $defaultTask = array(
            'giveCredit' => 0,
            'requireCredit' => 0,
            'materialNum' => 0,
            'quizNum' => 0,
            'viewedNum' => 0,
            'replayStatus' => 'ungenerated',
            'liveProvider' => 0,
            'testMode' => 'normal',
            'testStartTime' => 0,
            'summary' => $course['summary'],
            'exerciseId' => 0,
            'homeworkId' => 0,
            'mediaUri' => '',
            'mediaSource' => '',
        );

        if (empty($course['summary'])) {
            $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
            $defaultTask['summary'] = $courseSet['summary'];
        }

        $transformKeys = array(
            'isFree' => 'free',
            'createdUserId' => 'userId',
            'categoryId' => 'chapterId',
        );

        $items = array();
        $lessons = array();
        $number = 0;

        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds, true);
        $activities = ArrayToolkit::index($activities, 'id');

        foreach ($tasks as $task) {
            if ($this->isUselessTask($task, $course['type'])) {
                continue;
            }
            $task = array_merge($task, $defaultTask);
            $task['itemType'] = 'lesson';
            $task['number'] = ++$number;
            if ($task['type'] == 'doc') {
                $task['type'] = 'document';
            }
            foreach ($transformKeys as $key => $value) {
                $task[$value] = $task[$key];
            }
            $activity = $activities[$task['activityId']];
            $task = $this->filledTaskByActivity($task, $activity);
            $task['learnedNum'] = $this->getTaskResultService()->countTaskResults(
                array(
                    'courseTaskId' => $task['id'],
                    'status' => 'finish',
                )
            );
            $task['memberNum'] = $this->getTaskResultService()->countTaskResults(
                array(
                    'courseTaskId' => $task['id'],
                )
            );

            $task['content'] = $activity['content'];
            $lessons[] = $this->filterTask($task);
        }

        $chapters = $this->getChapterDao()->findChaptersByCourseId($course['id']);

        $chapterNumber = array(
            'unit' => 0,
            'lesson' => 0,
            'chapter' => 0,
        );

        foreach ($chapters as $chapter) {
            $chapter['itemType'] = 'chapter';
            $chapter['number'] = ++$chapterNumber[$chapter['type']];
            $items[] = $chapter;
        }
        uasort(
            $items,
            function ($item1, $item2) {
                return $item1['seq'] > $item2['seq'];
            }
        );

        return $lessons;
    }

    //移动端 数字转字符
    protected function filterTask($task)
    {
        array_walk(
            $task,
            function ($value, $key) use (&$task) {
                if (is_numeric($value)) {
                    $task[$key] = (string) $value;
                } else {
                    $task[$key] = $value;
                }
            }
        );

        return $task;
    }

    private function isUselessTask($task, $courseType)
    {
        $lessonTypes = array(
            'testpaper',
            'video',
            'audio',
            'text',
            'flash',
            'ppt',
            'doc',
            'live',
        );

        if ($courseType == 'live') {
            $lessonTypes = array('live', 'testpaper');
        }

        if (!in_array($task['type'], $lessonTypes)) {
            return true;
        }

        return false;
    }

    private function filledTaskByActivity($task, $activity)
    {
        $task['mediaId'] = isset($activity['ext']['mediaId']) ? $activity['ext']['mediaId'] : 0;

        if ($task['type'] == 'video') {
            $task['mediaSource'] = $activity['ext']['mediaSource'];
            $task['mediaUri'] = $activity['ext']['mediaUri'];
        } elseif ($task['type'] == 'audio') {
            $task['mediaSource'] = 'self';
        } elseif ($task['type'] == 'live') {
            if ($activity['ext']['replayStatus'] == 'videoGenerated') {
                $task['mediaSource'] = 'self';
            }

            $task['liveProvider'] = $activity['ext']['liveProvider'];
            $task['replayStatus'] = $activity['ext']['replayStatus'];
        }

        return $task;
    }

    public function findUserLearningCourseCountNotInClassroom($userId, $filters = array())
    {
        if (isset($filters['type'])) {
            return $this->getMemberDao()->countMemberNotInClassroomByUserIdAndCourseTypeAndIsLearned(
                $userId,
                'student',
                $filters['type'],
                0
            );
        }

        return $this->getMemberDao()->countMemberNotInClassroomByUserIdAndRoleAndIsLearned($userId, 'student', 0);
    }

    public function findUserLearningCoursesNotInClassroom($userId, $start, $limit, $filters = array())
    {
        if (isset($filters['type'])) {
            $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndCourseTypeAndIsLearned(
                $userId,
                'student',
                $filters['type'],
                '0',
                $start,
                $limit
            );
        } else {
            $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndRoleAndIsLearned(
                $userId,
                'student',
                0,
                $start,
                $limit
            );
        }

        $courses = $this->findCoursesByIds(ArrayToolkit::column($members, 'courseId'));

        $sortedCourses = array();

        foreach ($members as $member) {
            if (empty($courses[$member['courseId']])) {
                continue;
            }

            $course = $courses[$member['courseId']];
            $course['memberIsLearned'] = 0;
            $course['memberLearnedNum'] = $member['learnedNum'];
            $sortedCourses[] = $course;
        }

        return $sortedCourses;
    }

    public function findUserLeanedCourseCount($userId, $filters = array())
    {
        if (isset($filters['type'])) {
            return $this->getMemberDao()->countMemberByUserIdAndCourseTypeAndIsLearned(
                $userId,
                'student',
                $filters['type'],
                1
            );
        }

        return $this->getMemberDao()->countMemberByUserIdAndRoleAndIsLearned($userId, 'student', 1);
    }

    public function findUserLearnedCoursesNotInClassroom($userId, $start, $limit, $filters = array())
    {
        if (isset($filters['type'])) {
            $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndCourseTypeAndIsLearned(
                $userId,
                'student',
                $filters['type'],
                1,
                $start,
                $limit
            );
        } else {
            $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndRoleAndIsLearned(
                $userId,
                'student',
                1,
                $start,
                $limit
            );
        }

        $courses = $this->findCoursesByIds(ArrayToolkit::column($members, 'courseId'));

        $sortedCourses = array();

        foreach ($members as $member) {
            if (empty($courses[$member['courseId']])) {
                continue;
            }

            $course = $courses[$member['courseId']];
            $course['memberIsLearned'] = 1;
            $course['memberLearnedNum'] = $member['learnedNum'];
            $sortedCourses[] = $course;
        }

        return $sortedCourses;
    }

    public function findUserLearnCourseCountNotInClassroom($userId, $onlyPublished = true)
    {
        return $this->getMemberDao()->countMemberNotInClassroomByUserIdAndRole($userId, 'student', $onlyPublished);
    }

    public function findUserLearnCoursesNotInClassroom($userId, $start, $limit, $onlyPublished = true)
    {
        $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndRole(
            $userId,
            'student',
            $start,
            $limit,
            $onlyPublished
        );

        $courses = $this->findCoursesByIds(ArrayToolkit::column($members, 'courseId'));

        return $courses;
    }

    public function findUserLearnCoursesNotInClassroomWithType($userId, $type, $start, $limit, $onlyPublished = true)
    {
        $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndRoleAndType(
            $userId,
            'student',
            $type,
            $start,
            $limit,
            $onlyPublished
        );

        $courses = $this->findCoursesByIds(ArrayToolkit::column($members, 'courseId'));

        return $courses;
    }

    public function findUserTeachCourseCountNotInClassroom($conditions, $onlyPublished = true)
    {
        $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndRole(
            $conditions['userId'],
            'teacher',
            0,
            PHP_INT_MAX,
            $onlyPublished
        );
        unset($conditions['userId']);

        $courseIds = ArrayToolkit::column($members, 'courseId');
        $conditions['courseIds'] = $courseIds;

        if (count($courseIds) == 0) {
            return 0;
        }

        if ($onlyPublished) {
            $conditions['status'] = 'published';
        }

        return $this->searchCourseCount($conditions);
    }

    public function findUserTeachCoursesNotInClassroom($conditions, $start, $limit, $onlyPublished = true)
    {
        $members = $this->getMemberDao()->findMembersNotInClassroomByUserIdAndRole(
            $conditions['userId'],
            'teacher',
            $start,
            $limit,
            $onlyPublished
        );
        unset($conditions['userId']);

        $courseIds = ArrayToolkit::column($members, 'courseId');
        $conditions['courseIds'] = $courseIds;

        if (count($courseIds) == 0) {
            return array();
        }

        if ($onlyPublished) {
            $conditions['status'] = 'published';
        }

        $courses = $this->searchCourses($conditions, 'latest', 0, PHP_INT_MAX);

        return $courses;
    }

    /*
     * 2017/3/1 为移动端提供服务，其他慎用
     */
    public function findUserFavoritedCourseCountNotInClassroom($userId)
    {
        $courseFavorites = $this->getFavoriteDao()->findCourseFavoritesNotInClassroomByUserId($userId, 0, PHP_INT_MAX);
        $courseIds = ArrayToolkit::column($courseFavorites, 'courseId');
        $conditions = array('courseIds' => $courseIds);

        if (count($courseIds) == 0) {
            return 0;
        }

        return $this->searchCourseCount($conditions);
    }

    /*
     * 2017/3/1 为移动端提供服务，其他慎用
     */
    public function findUserFavoritedCoursesNotInClassroom($userId, $start, $limit)
    {
        $courseFavorites = $this->getFavoriteDao()->findCourseFavoritesNotInClassroomByUserId($userId, $start, $limit);
        $favoriteCourses = $this->getCourseDao()->findCoursesByIds(ArrayToolkit::column($courseFavorites, 'courseId'));

        return $favoriteCourses;
    }

    /*
     * 2017/3/1 为移动端提供服务，其他慎用
     */
    public function findUserFavoriteCoursesNotInClassroomWithCourseType($userId, $courseType, $start, $limit)
    {
        $coursesIds = $this->getFavoriteDao()->findUserFavoriteCoursesNotInClassroomWithCourseType(
            $userId,
            $courseType,
            $start,
            $limit
        );

        $courses = $this->findCoursesByIds(ArrayToolkit::column($coursesIds, 'id'));

        return $courses;
    }

    /*
     * 2017/3/1 为移动端提供服务，其他慎用
     */
    public function countUserFavoriteCourseNotInClassroomWithCourseType($userId, $courseType)
    {
        return $this->getFavoriteDao()->countUserFavoriteCoursesNotInClassroomWithCourseType(
            $userId,
            $courseType
        );
    }

    public function unlockCourse($courseId)
    {
        $course = $this->getCourseDao()->update($courseId, array('locked' => 0));

        $this->dispatchEvent('course.update', new Event($course));

        return $course;
    }

    protected function _prepareCourseOrderBy($sort)
    {
        if (is_array($sort)) {
            $orderBy = $sort;
        } elseif ($sort == 'popular' || $sort == 'hitNum') {
            $orderBy = array('hitNum' => 'DESC');
        } elseif ($sort == 'recommended') {
            $orderBy = array('recommendedTime' => 'DESC');
        } elseif ($sort == 'Rating') {
            $orderBy = array('Rating' => 'DESC');
        } elseif ($sort == 'studentNum') {
            $orderBy = array('studentNum' => 'DESC');
        } elseif ($sort == 'recommendedSeq') {
            $orderBy = array('recommendedSeq' => 'ASC', 'recommendedTime' => 'DESC');
        } elseif ($sort == 'createdTimeByAsc') {
            $orderBy = array('createdTime' => 'ASC');
        } else {
            $orderBy = array('createdTime' => 'DESC');
        }

        return $orderBy;
    }

    public function searchCourseCount($conditions)
    {
        $conditions = $this->_prepareCourseConditions($conditions);

        return $this->getCourseDao()->count($conditions);
    }

    public function countCourses(array $conditions)
    {
        return $this->getCourseDao()->count($conditions);
    }

    public function countCoursesGroupByCourseSetIds($courseSetIds)
    {
        return $this->getCourseDao()->countGroupByCourseSetIds($courseSetIds);
    }

    public function getFavoritedCourseByUserIdAndCourseSetId($userId, $courseSetId)
    {
        return $this->getFavoriteDao()->getByUserIdAndCourseSetId($userId, $courseSetId);
    }

    /**
     * @param $course
     *
     * @return CourseStrategy
     */
    protected function createCourseStrategy($course)
    {
        return $this->biz['course.strategy_context']->createStrategy($course['courseType']);
    }

    public function calculateLearnProgressByUserIdAndCourseIds($userId, array $courseIds)
    {
        if (empty($userId) || empty($courseIds)) {
            return array();
        }
        $courses = $this->findCoursesByIds($courseIds);

        $conditions = array(
            'courseIds' => $courseIds,
            'userId' => $userId,
        );
        $count = $this->getMemberService()->countMembers($conditions);
        $members = $this->getMemberService()->searchMembers(
            $conditions,
            array('id' => 'DESC'),
            0,
            $count
        );

        $learnProgress = array();
        foreach ($members as $member) {
            $learnProgress[] = array(
                'courseId' => $member['courseId'],
                'totalLesson' => $courses[$member['courseId']]['taskNum'],
                'learnedNum' => $member['learnedNum'],
            );
        }

        return $learnProgress;
    }

    public function buildCourseExpiryDataFromClassroom($expiryMode, $expiryValue)
    {
        $fields = array();
        if ($expiryMode === 'forever') {
            $fields = array(
                'expiryMode' => 'forever',
                'expiryDays' => 0,
                'expiryStartDate' => null,
                'expiryEndDate' => null,
            );
        } elseif ($expiryMode === 'days') {
            if ($expiryValue == 0) {
                $fields = array(
                    'expiryMode' => 'forever',
                    'expiryDays' => 0,
                    'expiryStartDate' => null,
                    'expiryEndDate' => null,
                );
            } else {
                $fields = array(
                    'expiryMode' => 'days',
                    'expiryDays' => $expiryValue,
                    'expiryStartDate' => null,
                    'expiryEndDate' => null,
                );
            }
        } elseif ($expiryMode === 'date') {
            $fields = array(
                'expiryMode' => 'end_date',
                'expiryDays' => 0,
                'expiryStartDate' => null,
                'expiryEndDate' => $expiryValue,
            );
        }

        return $fields;
    }

    public function hitCourse($courseId)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException("Course#{$courseId} Not Found");
        }

        return $this->getCourseDao()->wave(array($courseId), array('hitNum' => 1));
    }

    public function recountLearningData($courseId, $userId)
    {
        $member = $this->getMemberService()->getCourseMember($courseId, $userId);

        if (empty($member)) {
            throw $this->createAccessDeniedException('course.member_not_found');
        }

        $learnedNum = $this->getTaskResultService()->countTaskResults(
            array('courseId' => $courseId, 'userId' => $userId, 'status' => 'finish')
        );

        $learnedCompulsoryTaskNum = $this->getTaskResultService()->countFinishedCompulsoryTasksByUserIdAndCourseId($userId, $courseId);

        $this->getMemberService()->updateMember(
            $member['id'],
            array('learnedNum' => $learnedNum, 'learnedCompulsoryTaskNum' => $learnedCompulsoryTaskNum)
        );
    }

    public function tryFreeJoin($courseId)
    {
        $access = $this->canJoinCourse($courseId);

        if ($access['code'] != AccessorInterface::SUCCESS) {
            throw new UnableJoinException($access['msg'], $access['code']);
        }

        $course = $this->getCourse($courseId);

        if ($course['isFree'] == 1 || $course['originPrice'] == 0) {
            $this->getMemberService()->becomeStudent($course['id'], $this->getCurrentUser()->getId());
        }

        $this->dispatch('course.try_free_join', $course);
    }

    protected function hasAdminRole()
    {
        $user = $this->getCurrentUser();

        return $user->hasPermission('admin_course_content_manage');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    /**
     * @return CourseMemberDao
     */
    protected function getMemberDao()
    {
        return $this->createDao('Course:CourseMemberDao');
    }

    /**
     * @return CourseChapterDao
     */
    protected function getChapterDao()
    {
        return $this->createDao('Course:CourseChapterDao');
    }

    /**
     * @return CourseDao
     */
    protected function getCourseDao()
    {
        return $this->createDao('Course:CourseDao');
    }

    /**
     * @return CourseSetDao
     */
    protected function getCourseSetDao()
    {
        return $this->createDao('Course:CourseSetDao');
    }

    /**
     * @return ThreadDao
     */
    protected function getThreadDao()
    {
        return $this->createDao('Course:ThreadDao');
    }

    /**
     * @return FavoriteDao
     */
    protected function getFavoriteDao()
    {
        return $this->createDao('Course:FavoriteDao');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return MemberService
     */
    protected function getMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @return MaterialService
     */
    protected function getCourseMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    /**
     * @return ReviewService
     */
    protected function getReviewService()
    {
        return $this->createService('Course:ReviewService');
    }

    /**
     * @return CourseNoteService
     */
    protected function getNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    /**
     * @return CourseDeleteService
     */
    protected function getCourseDeleteService()
    {
        return $this->createService('Course:CourseDeleteService');
    }

    /**
     * @return ActivityServiceImpl
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * 当默认值未设置时，合并默认值
     *
     * @param  $course
     *
     * @return array
     */
    protected function mergeCourseDefaultAttribute($course)
    {
        $course = array_filter(
            $course,
            function ($value) {
                if ($value === '' || $value === null) {
                    return false;
                }

                return true;
            }
        );

        $default = array(
            'tryLookable' => 0,
            'originPrice' => 0.00,
        );

        return array_merge($default, $course);
    }

    /**
     * used for search userLearn userLearning userLearned.
     *
     * @param  $userId
     * @param  $filters
     *
     * @return array
     */
    protected function prepareUserLearnCondition($userId, $filters)
    {
        $filters = ArrayToolkit::parts($filters, array('type', 'classroomId', 'locked'));
        $conditions = array(
            'm.userId' => $userId,
            'm.role' => 'student',
        );
        if (!empty($filters['type'])) {
            $conditions['c.type'] = $filters['type'];
        }
        if (!empty($filters['classroomId'])) {
            $conditions['m.classroomId'] = $filters['classroomId'];
        }

        if (!empty($filters['locked'])) {
            $conditions['m.locked'] = $filters['locked'];
        }

        return $conditions;
    }

    /**
     * @param  $id
     * @param  $fields
     *
     * @return mixed
     */
    private function processFields($id, $fields, $courseSet)
    {
        if (isset($fields['originPrice'])) {
            list($fields['price'], $fields['coinPrice']) = $this->calculateCoursePrice($id, $fields['originPrice']);
        }

        if ($fields['isFree'] == 1) {
            $fields['price'] = 0;
        }

        if ($courseSet['type'] == 'normal' && $fields['tryLookable'] == 0) {
            $fields['tryLookLength'] = 0;
        }

        if (!empty($fields['buyExpiryTime'])) {
            if (is_numeric($fields['buyExpiryTime'])) {
                $fields['buyExpiryTime'] = date('Y-m-d', $fields['buyExpiryTime']);
            }

            $fields['buyExpiryTime'] = strtotime($fields['buyExpiryTime'].' 23:59:59');
        } else {
            $fields['buyExpiryTime'] = 0;
        }

        return $fields;
    }

    protected static function learnModes()
    {
        return array(
            static::FREE_LEARN_MODE,
            static::LOCK_LEARN_MODE,
        );
    }

    protected static function courseTypes()
    {
        return array(
            static::DEFAULT_COURSE_TYPE,
            static::NORMAL__COURSE_TYPE,
        );
    }
}
