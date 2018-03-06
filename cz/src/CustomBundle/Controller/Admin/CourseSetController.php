<?php

namespace CustomBundle\Controller\Admin;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class CourseSetController extends BaseController
{
    public function indexAction(Request $request, $filter)
    {
        $conditions = $request->query->all();

        $conditions = $this->fillOrgCode($conditions);
        $conditions = $this->filterConditions($conditions);
        $conditions['status'] = 'published';
        $count = $this->getCourseSetService()->countCourseSetsWithCourseNo($conditions);

        if (!empty($conditions['categoryId'])) {
            $conditions['categoryIds'] = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
            $conditions['categoryIds'][] = $conditions['categoryId'];
            unset($conditions['categoryId']);
        }

        $paginator = new Paginator($this->get('request'), $count, 20);
        $courseSets = $this->getCourseSetService()->searchCourseSets(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $courseSetIds = ArrayToolkit::column($courseSets, 'id');

        list($searchCourseSetsNum, $publishedCourseSetsNum, $closedCourseSetsNum, $unPublishedCourseSetsNum) = $this->getDifferentCourseSetsNum(
            $conditions
        );

        $categories = $this->getCategoryService()->findCategoriesByIds(ArrayToolkit::column($courseSets, 'categoryId'));

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($courseSets, 'userId'));

        $courseSetting = $this->getSettingService()->get('course', array());

        if (!isset($courseSetting['live_course_enabled'])) {
            $courseSetting['live_course_enabled'] = '';
        }

        $default = $this->getSettingService()->get('default', array());

        if (!empty($conditions['termCode'])) {
            $courses = $this->getCourseService()->searchCourses(array(
                'courseSetIds' => $courseSetIds,
                'termCode' => $conditions['termCode']),
                array(),
                0,
                PHP_INT_MAX);
        } else {
            $courses = $this->getCourseService()->findCoursesByCourseSetIds($courseSetIds);

        }

        $courses = $this->findTeachedLesson($courses);
        $groupCourses = ArrayToolkit::group($courses, 'courseSetId');

        return $this->render(
            'admin/instant-course-set/index.html.twig',
            array(
                'conditions' => $conditions,
                'courseSets' => $courseSets,
                'courses' => $groupCourses,
                'users' => $users,
                'categories' => $categories,
                'paginator' => $paginator,
                'liveSetEnabled' => $courseSetting['live_course_enabled'],
                'default' => $default,
                'searchCourseSetsNum' => $searchCourseSetsNum,
                'publishedCourseSetsNum' => $publishedCourseSetsNum,
                'closedCourseSetsNum' => $closedCourseSetsNum,
                'unPublishedCourseSetsNum' => $unPublishedCourseSetsNum,
                'termCode' => $this->getTermCode($conditions),
            )
        );
    }

    private function filterConditions($conditions)
    {
        $conditions['ids'] = array();
        if (!empty($conditions['termCode'])) {
            $courses = $this->getCourseService()->findInstantCoursesByTermCode($conditions['termCode']);
            $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
            $conditions['ids'] = array_unique($courseSetIds);
            if (empty($courses)) {
                return array('type' => 1);
            }
        }

        if (!empty($conditions['truename'])) {
            $condition['truename'] = $conditions['truename'];
            $users = $this->getUserService()->searchUserProfiles($condition, array(), 0, PHP_INT_MAX);
            if (empty($users)) {
                return array('type' => 1);
            }

            $userCourseIds = array();
            $userIds = ArrayToolkit::column($users, 'id');
            $members = $this->getCourseMemberService()->findTeacherMembersByUserIds($userIds);
            $courseIds = ArrayToolkit::column($members, 'courseId');
            $courses = $this->getCourseService()->findCoursesByIds($courseIds);
            $userCourseIds = ArrayToolkit::column($courses, 'courseSetId');
            $userCourseIds = array_unique($userCourseIds);

            $users = $this->getUserService()->findUsersByIds($userIds);
            $courseIds = ArrayToolkit::column($users, 'userId');
            $teachers = array();
            foreach ($members as $member) {
                if (empty($users[$member['userId']])) {
                    $teachers[$member['courseId']] = $users[$member['userId']];
                }
            }
            $conditions['userId'] = $teachers;

            if (empty($conditions['termCode'])) {
                $conditions['ids'] = $userCourseIds;
            } else {
                $conditions['ids'] = array_intersect($conditions['ids'], $userCourseIds);
            }
        }
        $conditions['type'] = 'instant';

        return $conditions;
    }

    protected function getTermCode($conditions)
    {
        if (empty($conditions['termCode'])) {
            return false;
        }

        return $this->getCourseService()->getTermByShortCode($conditions['termCode']);
    }

    private function findTeachedLesson($courses)
    {
        $courseIds = ArrayToolkit::column($courses, 'id');
        $lessons = $this->getCourseLessonService()->findCountLessonByCourseIds($courseIds);
        $teachedLessons = $this->getCourseLessonService()->findCountLessonByCourseIdsAndStatus($courseIds, 'teached');

        foreach ($courses as &$course) {
            $course['teachedLessonCount'] = empty($teachedLessons[$course['id']]) ? 0 : $teachedLessons[$course['id']]['count'];
            if (isset($lessons[$course['id']])) {
                $course['lessonCount'] = $lessons[$course['id']]['count'];
            }
        }

        return $courses;
    }

    private function getTeachingInstantCourses($request, $userId, $termCode, $courseType)
    {
        $members = $this->getCourseMemberService()->findCurrentTermTeacherMembersByUserId($userId, $termCode);
        if (empty($members)) {
            $paginator = new Paginator(
                $request,
                0,
                20
            );

            return array(
                'paginator' => $paginator,
                'courses' => array(),
                'courseSets' => array(),
                'termCode' => $termCode,
                'courseType' => $courseType,
            );
        }

        $courseSetIds = ArrayToolkit::column($members, 'courseSetId');
        $courseSetIds = array_unique($courseSetIds);

        $conditions = array(
            'ids' => $courseSetIds,
            'type' => 'instant',
            'status' => 'published',
            'termCode' => $termCode,
            'courseType' => $courseType,
        );

        $perCount = 5;
        $orderBy = 'ASC';

        if ($courseType == 'custom') {
            $perCount = 10;
            $orderBy = 'DESC';
        }

        $paginator = new Paginator(
            $request,
            $this->getCourseSetService()->countCourseSetsWithCourseNo($conditions),
            $perCount
        );

        $courseSets = $this->getCourseSetService()->searchCourseSetsWithCourseNo(
            $conditions,
            array('createdTime' => $orderBy),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $members = ArrayToolkit::group($members, 'courseSetId');
        $courseIds = array();
        foreach ($courseSets as $courseSet) {
            $tempCourseIds = ArrayToolkit::column($members[$courseSet['id']], 'courseId');
            $courseIds = array_merge($courseIds, $tempCourseIds);
        }

        $courses = $this->getCourseService()->findCoursesByIds($courseIds);
        if ($courseType == 'custom') {
            $courses = ArrayToolkit::index($courses, 'courseSetId');
        } else {
            $courses = ArrayToolkit::group($courses, 'courseSetId');
        }

        return array(
            'paginator' => $paginator,
            'courses' => $courses,
            'courseSets' => $courseSets,
            'termCode' => $termCode,
            'courseType' => $courseType,
        );
    }

    protected function getDifferentCourseSetsNum($conditions)
    {
        $courseSets = $this->getCourseSetService()->searchCourseSets(
            $conditions,
            array(),
            0,
            PHP_INT_MAX
        );

        $publishedCourseSetsNum = 0;
        $closedCourseSetsNum = 0;
        $unPublishedCourseSetsNum = 0;
        $searchCourseSetsNum = count($courseSets);

        foreach ($courseSets as $courseSet) {
            if ($courseSet['status'] == 'published') {
                ++$publishedCourseSetsNum;
            }

            if ($courseSet['status'] == 'closed') {
                ++$closedCourseSetsNum;
            }

            if ($courseSet['status'] == 'draft') {
                ++$unPublishedCourseSetsNum;
            }
        }

        return array($searchCourseSetsNum, $publishedCourseSetsNum, $closedCourseSetsNum, $unPublishedCourseSetsNum);
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
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
     * @return LevelService
     */
    protected function getVipLevelService()
    {
        return $this->createService('VipPlugin:Vip:LevelService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
}
