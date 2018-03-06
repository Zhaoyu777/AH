<?php

namespace AppBundle\Controller\Course;

use AppBundle\Common\Paginator;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Biz\User\Service\TokenService;
use Biz\Order\Service\OrderService;
use Biz\Course\Service\ReviewService;
use Biz\Course\Service\MaterialService;
use Biz\File\Service\UploadFileService;
use Biz\Task\Service\TaskResultService;
use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseNoteService;
use Biz\Taxonomy\Service\CategoryService;
use VipPlugin\Biz\Vip\Service\VipService;
use Biz\Classroom\Service\ClassroomService;
use Symfony\Component\HttpFoundation\Request;

class CourseController extends CourseBaseController
{
    public function summaryAction($course, $member = array())
    {
        list($isMarketingPage, $member) = $this->isMarketingPage($course['id'], $member);

        $courseItems = array();
        if ($isMarketingPage) {
            list($courseItems) = $this->getCourseService()->findCourseItemsByPaging($course['id']);
        }

        $course['courseNum'] = $this->getCourseNumInCourseSet($course['courseSetId']);

        return $this->render(
            'course/tabs/summary.html.twig',
            array(
                'course' => $course,
                'member' => $member,
                'isMarketingPage' => $isMarketingPage,
                'courseItems' => $courseItems,
            )
        );
    }

    public function showAction(Request $request, $id, $tab = 'summary')
    {
        $tab = $this->prepareTab($tab);
        $user = $this->getCurrentUser();

        $course = $this->getCourseService()->getCourse($id);
        if (empty($course)) {
            throw $this->createNotFoundException('该教学计划不存在！');
        }

        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        if (empty($courseSet)) {
            throw $this->createNotFoundException('该教学计划所属课程不存在！');
        }
        /**
        if ($user->isLogin() && $this->canCourseShowRedirect($request)) {
            $lastCourseMember = $this->getMemberService()->searchMembers(
                array(
                    'userId' => $user['id'],
                    'courseSetId' => $course['courseSetId'],
                ),
                array('lastLearnTime' => 'desc'),
                0,
                1
            );
            if (!empty($lastCourseMember)) {
                $lastCourseMember = reset($lastCourseMember);
                $course = $this->getCourseService()->getCourse($lastCourseMember['courseId']);
                //周期课程且未开始时，不做跳转
                if ($course['expiryMode'] != 'date' || $course['expiryStartDate'] < time()) {
                    return $this->redirect(($this->generateUrl('my_course_show', array('id' => $lastCourseMember['courseId']))));
                }
            }
        }
        **/
        if ($this->isPluginInstalled('Discount')) {
            $discount = $this->getDiscountService()->getDiscount($courseSet['discountId']);
            if (!empty($discount)) {
                $course['discount'] = $discount;
            }
        }

        $classroom = array();
        if ($course['parentId'] > 0) {
            $classroom = $this->getClassroomService()->getClassroomByCourseId($course['id']);
        }

        $isCourseTeacher = $this->getMemberService()->isCourseTeacher($id, $user['id']);

        $this->getCourseService()->hitCourse($id);

        $tags = $this->findCourseSetTagsByCourseSetId($course['courseSetId']);

        $member = $this->getCourseMember($request, $course);

        return $this->render(
            'course/course-show.html.twig',
            array(
                'tab' => $tab,
                'tags' => $tags,
                'course' => $course,
                'categoryTag' => $this->calculateCategoryTag($course),
                'classroom' => $classroom,
                'isCourseTeacher' => $isCourseTeacher,
                'navMember' => $member,
            )
        );
    }

    private function canCourseShowRedirect($request)
    {
        $host = $request->getHost();
        $referer = $request->headers->get('referer');
        if (empty($referer)) {
            return false;
        }

        $matchExpre = "/{$host}\/(my\/)?course\/(\d)+/i";
        if (preg_match($matchExpre, $referer)) {
            return false;
        }

        return true;
    }

    private function calculateCategoryTag($course)
    {
        $tasks = $this->getTaskService()->findTasksByCourseId($course['id']);
        if (empty($tasks)) {
            return null;
        }
        $tag = null;
        foreach ($tasks as $task) {
            if (empty($tag) && $task['type'] === 'video' && $course['tryLookable']) {
                $activity = $this->getActivityService()->getActivity($task['activityId'], true);
                if (!empty($activity['ext']['file']) && $activity['ext']['file']['storage'] === 'cloud') {
                    $tag = 'site.badge.try_watch';
                }
            }
            //tag的权重：免费优先于试看
            if ($task['isFree']) {
                return 'site.badge.free';
            }
        }

        return $tag;
    }

    public function memberExpiredAction($id)
    {
        list($course, $member) = $this->getCourseService()->tryTakeCourse($id);

        if ($course['parentId'] > 0) {
            $classroomRef = $this->getClassroomService()->getClassroomCourseByCourseSetId($course['courseSetId']);
            if (!empty($classroomRef)) {
                $user = $this->getCurrentUser();
                $member = $this->getClassroomService()->getClassroomMember($classroomRef['classroomId'], $user['id']);
                if ($member['deadline'] > 0 && $member['deadline'] < time()) {
                    return $this->render(
                        'course/member/classroom-course-expired.html.twig',
                        array(
                            'course' => $course,
                            'member' => $member,
                        )
                    );
                }

                return $this->createJsonResponse(true);
            }
        }

        if ($this->getMemberService()->isMemberNonExpired($course, $member)) {
            return $this->createJsonResponse(true);
        }

        return $this->render(
            'course/member/normal-course-expired.html.twig',
            array(
                'course' => $course,
                'member' => $member,
            )
        );
    }

    public function deadlineReachAction($id)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('不允许未登录访问');
        }

        $this->getMemberService()->quitCourseByDeadlineReach($user['id'], $id);

        return $this->redirect($this->generateUrl('course_show', array('id' => $id)));
    }

    public function headerAction(Request $request, $course)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $courses = $this->getCourseService()->findCoursesByCourseSetId($course['courseSetId']);

        $breadcrumbs = $this->getCategoryService()->findCategoryBreadcrumbs($courseSet['categoryId']);
        $user = $this->getCurrentUser();

        $member = $user->isLogin() ? $this->getMemberService()->getCourseMember(
            $course['id'],
            $user['id']
        ) : array();

        $isUserFavorite = $user->isLogin() ? $this->getCourseSetService()->isUserFavorite(
            $user['id'],
            $course['courseSetId']
        ) : false;

        $previewAs = $request->query->get('previewAs', null);
        $classroom = $this->getClassroomService()->getClassroomByCourseId($course['id']);
        if ($user->isLogin() && !empty($classroom) && $classroom['headTeacherId'] == $user['id']) {
            $member = $this->createMemberFromClassroomHeadteacher($course, $classroom);
        }

        $previewTasks = $this->getTaskService()->searchTasks(
            array('courseId' => $course['id'], 'type' => 'video', 'isFree' => '1'),
            array('seq' => 'ASC'),
            0,
            1
        );

        return $this->render(
            'course/header/header-for-guest.html.twig',
            array(
                'isUserFavorite' => $isUserFavorite,
                'member' => $member,
                'courseSet' => $courseSet,
                'courses' => $courses,
                'course' => $course,
                'classroom' => $classroom,
                'previewTask' => empty($previewTasks) ? null : array_shift($previewTasks),
                'previewAs' => $previewAs,
                'marketingPage' => 1,
                'breadcrumbs' => $breadcrumbs,
            )
        );
    }

    private function createMemberFromClassroomHeadteacher($course, $classroom)
    {
        return array(
            'id' => 0,
            'courseSetId' => $course['courseSetId'],
            'courseId' => $course['id'],
            'classroomId' => $classroom['id'],
            'userId' => $classroom['headTeacherId'],
            'deadline' => 0,
            'role' => 'teacher',
            'isVisible' => 0,
            'locked' => 0,
        );
    }

    public function notesAction(Request $request, $course, $member = array())
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $sort = $request->query->get('sort', 'latest');

        $selectedCourseId = $this->getSelectCourseId($request, $course);

        if (empty($selectedCourseId)) {
            $tasks = $this->getTaskService()->findTasksByCourseSetId($courseSet['id']);
        } else {
            $tasks = $this->getTaskService()->findTasksByCourseId($selectedCourseId);
        }

        $tasks = ArrayToolkit::index($tasks, 'id');

        $conditions = array(
            'status' => CourseNoteService::PUBLIC_STATUS,
            'taskId' => $request->query->get('task'),
            'courseSetId' => $course['courseSetId'],
            'courseId' => $selectedCourseId ? $selectedCourseId : '',
        );

        $paginator = new Paginator(
            $request,
            $this->getCourseNoteService()->countCourseNotes($conditions),
            20
        );

        $notes = $this->getCourseNoteService()->searchNotes(
            $conditions,
            $this->getNoteOrdersBySort($sort),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($notes, 'userId'));
        $users = ArrayToolkit::index($users, 'id');

        $currentUser = $this->getCurrentUser();
        $likes = $this->getCourseNoteService()->findNoteLikesByUserId($currentUser['id']);
        $likeNoteIds = ArrayToolkit::column($likes, 'noteId');

        return $this->render(
            'course/tabs/notes.html.twig',
            array(
                'course' => $course,
                'currentRequest' => $request,
                'paginator' => $paginator,
                'selectedCourseId' => $selectedCourseId,
                'courseSet' => $courseSet,
                'notes' => $notes,
                'users' => $users,
                'tasks' => $tasks,
                'likeNoteIds' => $likeNoteIds,
                'member' => $member,
                'currentRoute' => $this->get('request_stack')->getMasterRequest()->get('_route'),
            )
        );
    }

    public function reviewsAction(Request $request, $course, $member = array())
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $selectedCourseId = $this->getSelectCourseId($request, $course);

        $conditions = array(
            'parentId' => 0,
            'courseSetId' => $courseSet['id'],
            'courseId' => $selectedCourseId ? $selectedCourseId : '',
        );

        $paginator = new Paginator(
            $request,
            $this->getReviewService()->searchReviewsCount($conditions),
            20
        );

        $reviews = $this->getReviewService()->searchReviews(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userReview = array();
        $user = $this->getCurrentUser();
        if (empty($member) && $user->isLogin()) {
            $member = $this->getMemberService()->getCourseMember($course['id'], $user['id']);
        }
        if (!empty($member)) {
            if ($selectedCourseId > 0) {
                $userReview = $this->getReviewService()->getUserCourseReview($member['userId'], $selectedCourseId);
            } else {
                $userReview = $this->getReviewService()->getUserCourseReview($member['userId'], $course['id']);
            }
        }

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));
        $courses = $this->getCourseService()->findPublishedCoursesByCourseSetId($courseSet['id']);

        return $this->render(
            'course/tabs/reviews.html.twig',
            array(
                'courseSet' => $courseSet,
                'paginator' => $paginator,
                'selectedCourseId' => $selectedCourseId,
                'courses' => $courses,
                'courseMap' => ArrayToolkit::index($courses, 'id'),
                'course' => $course,
                'reviews' => $reviews,
                'userReview' => $userReview,
                'users' => $users,
                'member' => $member,
            )
        );
    }

    /**
     * @param Request $request
     * @param $course
     *
     * @return string
     */
    private function getSelectCourseId(Request $request, $course)
    {
        if ($request->get('_route') == 'my_course_show') {
            return $request->query->get('selectedCourse', $course['id']);
        } else {
            return $request->query->get('selectedCourse', 0);
        }
    }

    public function coursesBlockAction($courses, $view = 'list', $mode = 'default')
    {
        $userIds = array();

        foreach ($courses as $key => $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
            $classroomIds = $this->getClassroomService()->findClassroomIdsByCourseId($course['id']);

            $courses[$key]['classroomCount'] = count($classroomIds);
            if (count($classroomIds) > 0) {
                $classroomId = $classroomIds[0]['classroomId'];
                $classroom = $this->getClassroomService()->getClassroom($classroomId);

                $courses[$key]['classroom'] = $classroom;
            }
        }

        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render(
            "course/block/courses-block-{$view}.html.twig",
            array(
                'courses' => $courses,
                'users' => $users,
                'mode' => $mode,
            )
        );
    }

    public function tasksAction($course, $member = array())
    {
        list($isMarketingPage, $member) = $this->isMarketingPage($course['id'], $member);

        list($courseItems, $nextOffsetSeq) = $this->getCourseService()->findCourseItemsByPaging($course['id']);

        return $this->render(
            'course/tabs/tasks.html.twig',
            array(
                'course' => $course,
                'courseItems' => $courseItems,
                'nextOffsetSeq' => $nextOffsetSeq,
                'member' => $member,
                'isMarketingPage' => $isMarketingPage,
            )
        );
    }

    public function tasksByPagingAction(Request $request, $courseId)
    {
        $offsetSeq = $request->query->get('offsetSeq');
        $direction = $request->query->get('direction', 'down');
        $course = $this->getCourseService()->getCourse($courseId);
        $member = $this->getMemberService()->getCourseMember($courseId, $this->getCurrentUser()->getId());
        list($courseItems, $nextOffsetSeq) = $this->getCourseService()->findCourseItemsByPaging($courseId, array('offsetSeq' => $offsetSeq, 'direction' => $direction));

        return $this->render(
            'course/tabs/tasks.html.twig',
            array(
                'course' => $course,
                'member' => $member,
                'nextOffsetSeq' => $nextOffsetSeq,
                'courseItems' => $courseItems,
            )
        );
    }

    public function characteristicAction($course)
    {
        $tasks = $this->getTaskService()->findTasksFetchActivityByCourseId($course['id']);

        $characteristicData = array();
        $activities = $this->get('extension.manager')->getActivities();

        foreach ($tasks as $task) {
            // $type = strtolower($task['activity']['mediaType']);
            $type = $task['activity']['mediaType'];
            if (isset($characteristicData[$type])) {
                ++$characteristicData[$type]['num'];
            } else {
                $characteristicData[$type] = array(
                    'icon' => $activities[$type]['meta']['icon'],
                    'name' => $activities[$type]['meta']['name'],
                    'num' => 1,
                );
            }
        }

        return $this->render(
            'course/widgets/characteristic.html.twig',
            array(
                'course' => $course,
                'characteristicData' => $characteristicData,
            )
        );
    }

    public function otherCoursesAction($course, $member)
    {
        $limitNum = 5;
        $user = $this->getCurrentUser();
        $unPurchasedCourse = array();

        $otherCoursesMember = $this->getMemberService()->searchMembers(
            array(
                'userId' => $user['id'],
                'courseSetId' => $course['courseSetId'],
                'excludeIds' => array($member['id']),
            ),
            array('lastLearnTime' => 'desc'),
            0,
            $limitNum
        );
        $purchasedCourseIds = ArrayToolkit::column($otherCoursesMember, 'courseId');

        if (count($otherCoursesMember) < $limitNum) {
            $excludeCourseIds = $purchasedCourseIds;
            $excludeCourseIds[] = $member['courseId'];

            $unPurchasedCourse = $this->getCourseService()->searchCourses(
                array(
                    'courseSetId' => $course['courseSetId'],
                    'excludeIds' => $excludeCourseIds,
                    'status' => 'published',
                ),
                array('createdTime' => 'desc'),
                0,
                $limitNum - count($otherCoursesMember)
            );
        }

        $purchasedCourse = $this->getCourseService()->findCoursesByIds($purchasedCourseIds);
        $otherCourses = array_merge($purchasedCourse, $unPurchasedCourse);

        return $this->render(
            'course/widgets/other-courses.html.twig', array(
                'course' => $course,
                'otherCourses' => $otherCourses,
                'purchasedCourseIds' => $purchasedCourseIds,
            )
        );
    }

    public function teachersAction($course)
    {
        $teacherIds = $course['teacherIds'];
        $teachers = $this->getUserService()->findUsersByIds($teacherIds);
        if (!empty($teachers)) {
            //确保教师按照中台教师管理设置的顺序展示
            usort(
                $teachers,
                function ($t1, $t2) use ($teacherIds) {
                    if (array_search($t1['id'], $teacherIds) < array_search($t2['id'], $teacherIds)) {
                        return -1;
                    }

                    return 1;
                }
            );
        }

        return $this->render(
            'course/widgets/teachers.html.twig',
            array(
                'teachers' => $teachers,
            )
        );
    }

    public function newestStudentsAction($course, $member = array())
    {
        $conditions = array(
            'role' => 'student',
            'locked' => 0,
        );

        if (empty($member)) {
            $courses = $this->getCourseService()->findCoursesByCourseSetId($course['courseSetId']);
            $conditions['courseIds'] = ArrayToolkit::column($courses, 'id');
        } else {
            $conditions['courseId'] = $course['id'];
        }

        $members = $this->getMemberService()->searchMembers($conditions, array('createdTime' => 'DESC'), 0, 20);
        $studentIds = ArrayToolkit::column($members, 'userId');
        $students = $this->getUserService()->findUsersByIds($studentIds);

        return $this->render(
            'course/widgets/newest-students.html.twig',
            array(
                'students' => $students,
            )
        );
    }

    public function orderInfoAction($sn)
    {
        $order = $this->getOrderService()->getOrderBySn($sn);

        if (empty($order)) {
            throw $this->createNotFoundException('订单不存在!');
        }

        $course = $this->getCourseService()->getCourse($order['targetId']);

        if (empty($course)) {
            throw $this->createNotFoundException('课程不存在，或已删除。');
        }

        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->render(
            'course/widgets/course-order.html.twig',
            array(
                'order' => $order,
                'course' => $course,
                'courseSet' => $courseSet,
            )
        );
    }

    public function qrcodeAction(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        $host = $request->getSchemeAndHttpHost();

        $url = $this->generateUrl('course_show', array('id' => $id), true);
        if ($user->isLogin()) {
            $courseMember = $this->getMemberService()->getCourseMember($id, $user['id']);
            if ($courseMember) {
                $url = $this->generateUrl('my_course_show', array('id' => $id), true);
            }
        }

        $token = $this->getTokenService()->makeToken(
            'qrcode',
            array(
                'userId' => $user['id'],
                'data' => array(
                    'url' => $url,
                    'appUrl' => "{$host}/mapi_v2/mobile/main#/course/{$id}",
                ),
                'times' => 1,
                'duration' => 3600,
            )
        );
        $url = $this->generateUrl('common_parse_qrcode', array('token' => $token['token']), true);

        $response = array(
            'img' => $this->generateUrl('common_qrcode', array('text' => $url), true),
        );

        return $this->createJsonResponse($response);
    }

    public function exitAction(Request $request, $id)
    {
        list($course, $member) = $this->getCourseService()->tryTakeCourse($id);
        if (empty($member)) {
            throw $this->createAccessDeniedException('member not exist');
        }

        $user = $this->getCurrentUser();
        $req = $request->request->all();
        $this->getMemberService()->removeStudent($course['id'], $user['id'], array(
            'reason' => $req['reason']['note'],
            'reason_type' => 'exit',
        ));

        return $this->redirect($this->generateUrl('course_show', array('id' => $id)));
    }

    public function exitModalAction(Request $request)
    {
        $action = $request->query->get('action');

        return $this->render('course/exit-modal.html.twig', array(
            'action' => $action,
        ));
    }

    public function renderCourseChoiceAction()
    {
        $masterRequest = $this->get('request_stack')->getMasterRequest();
        $routeParams = $masterRequest->attributes->get('_route_params');
        $currentCourse = $this->getCourseService()->getCourse($routeParams['id']);

        $selectedCourseId = $this->getSelectCourseId($masterRequest, $currentCourse);

        return $this->render('course/tabs/widget/course-choice.html.twig', array(
            'currentRoute' => $masterRequest->get('_route'),
            'currentCourse' => $currentCourse,
            'courses' => $this->getCourseService()->findCoursesByCourseSetId($currentCourse['courseSetId']),
            'tab' => $routeParams['tab'],
            'selectedCourseId' => $selectedCourseId,
        ));
    }

    protected function getNoteOrdersBySort($sort)
    {
        switch ($sort) {
            case 'latest':
                return array('createdTime' => 'DESC');
            case 'like':
                return array('likeNum' => 'DESC');
            default:
                break;
        }

        return array('createdTime' => 'DESC');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return CourseNoteService
     */
    protected function getCourseNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    /**
     * @return VipService
     */
    protected function getVipService()
    {
        return $this->createService('VipPlugin:Vip:VipService');
    }

    protected function getDiscountService()
    {
        return $this->createService('DiscountPlugin:Discount:DiscountService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    /**
     * @return ReviewService
     */
    protected function getReviewService()
    {
        return $this->createService('Course:ReviewService');
    }

    /**
     * @return OrderService
     */
    protected function getOrderService()
    {
        return $this->createService('Order:OrderService');
    }

    /**
     * @return MaterialService
     */
    protected function getMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    /**
     * @return TokenService
     */
    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @param  $courseId
     * @param  $member
     *
     * @return array
     */
    protected function isMarketingPage($courseId, $member)
    {
        $isMarketingPage = false;
        if (empty($member)) {
            $isMarketingPage = true;
            $user = $this->getCurrentUser();
            $member = $user->isLogin() ? $this->getMemberService()->getCourseMember(
                $courseId,
                $user['id']
            ) : array();

            return array($isMarketingPage, $member);
        }

        return array($isMarketingPage, $member);
    }

    /**
     * @param  $tab
     *
     * @return string
     */
    protected function prepareTab($tab)
    {
        $metas = $this->container->get('course.extension')->getCourseShowMetas();
        $tabs = array_keys($metas['for_guest']['tabs']);
        if (!in_array($tab, $tabs)) {
            $tab = 'summary';

            return $tab;
        }

        return $tab;
    }

    protected function getCourseNumInCourseSet($courseSetId)
    {
        $courseNums = $this->getCourseService()->countCoursesGroupByCourseSetIds(array($courseSetId));
        if (!empty($courseNums)) {
            return $courseNums[0]['courseNum'];
        }

        return 1;
    }
}
