<?php

namespace AppBundle\Controller;

use AppBundle\Common\Paginator;
use Biz\Course\Service\CourseSetService;
use Biz\Util\EdusohoLiveClient;
use AppBundle\Common\ExportHelp;
use AppBundle\Common\ArrayToolkit;
use Biz\Content\Service\FileService;
use Biz\Taxonomy\Service\TagService;
use Biz\Course\Service\CourseService;
use Biz\System\Service\SettingService;
use Biz\User\Service\UserFieldService;
use Biz\File\Service\UploadFileService;
use Biz\OpenCourse\Service\OpenCourseService;
use Symfony\Component\HttpFoundation\Request;
use Biz\OpenCourse\Processor\CourseProcessorFactory;
use Biz\OpenCourse\Service\OpenCourseRecommendedService;

class OpenCourseManageController extends BaseController
{
    public function indexAction(Request $request, $id)
    {
        return $this->forward('AppBundle:OpenCourseManage:base', array('id' => $id));
    }

    public function baseAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();

            $this->getOpenCourseService()->updateCourse($id, $data);
            $this->setFlashMessage('success', 'site.save.success');

            return $this->redirect($this->generateUrl('open_course_manage_base', array('id' => $id)));
        }

        $tags = $this->getTagService()->findTagsByOwner(array('ownerType' => 'openCourse', 'ownerId' => $id));

        $default = $this->getSettingService()->get('default', array());

        return $this->render(
            'open-course-manage/base-info.html.twig',
            array(
                'course' => $course,
                'tags' => ArrayToolkit::column($tags, 'name'),
                'default' => $default,
            )
        );
    }

    /**
     * @return OpenCourseService
     */
    protected function getOpenCourseService()
    {
        return $this->getBiz()->service('OpenCourse:OpenCourseService');
    }

    /**
     * @return TagService
     */
    protected function getTagService()
    {
        return $this->getBiz()->service('Taxonomy:TagService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }

    public function pictureAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        return $this->render(
            'open-course-manage/picture.html.twig',
            array(
                'course' => $course,
            )
        );
    }

    public function pictureCropAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->getOpenCourseService()->changeCoursePicture($course['id'], $data['images']);

            return $this->redirect($this->generateUrl('open_course_manage_picture', array('id' => $course['id'])));
        }

        $fileId = $request->getSession()->get('fileId');
        list($pictureUrl, $naturalSize, $scaledSize) = $this->getFileService()->getImgFileMetaInfo($fileId, 480, 270);

        return $this->render(
            'open-course-manage/picture-crop.html.twig',
            array(
                'course' => $course,
                'pictureUrl' => $pictureUrl,
                'naturalSize' => $naturalSize,
                'scaledSize' => $scaledSize,
            )
        );
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->getBiz()->service('Content:FileService');
    }

    public function teachersAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            if (empty($data) || !isset($data['teachers'])) {
                return $this->redirect($this->generateUrl('open_course_manage_teachers', array('id' => $id)));
            }

            $teachers = json_decode($data['teachers'], true);

            $this->getOpenCourseService()->setCourseTeachers($id, $teachers);

            $this->setFlashMessage('success', 'site.save.success');

            return $this->redirect($this->generateUrl('open_course_manage_teachers', array('id' => $id)));
        }

        $teacherMembers = $this->getOpenCourseService()->searchMembers(
            array(
                'courseId' => $id,
                'role' => 'teacher',
                'isVisible' => 1,
            ),
            array('seq' => 'ASC'),
            0,
            100
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($teacherMembers, 'userId'));

        $teacherIds = array();

        foreach ($teacherMembers as $member) {
            if (empty($users[$member['userId']])) {
                continue;
            }

            $teacherIds[] = array(
                'id' => $member['userId'],
                'nickname' => $users[$member['userId']]['nickname'],
                'avatar' => $this->getWebExtension()->getFilePath(
                    $users[$member['userId']]['smallAvatar'],
                    'avatar.png'
                ),
                'isVisible' => $member['isVisible'] ? true : false,
            );
        }

        //获取直播供应商
        $client = new EdusohoLiveClient();
        $capacity = $client->getCapacity();

        return $this->render(
            'open-course-manage/teachers.html.twig',
            array(
                'course' => $course,
                'teacherIds' => $teacherIds,
                'capacity' => $capacity,
            )
        );
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getWebExtension()
    {
        return $this->container->get('web.twig.extension');
    }

    public function teachersMatchAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $queryField = $request->query->get('q');
        $users = $this->getUserService()->searchUsers(
            array('nickname' => $queryField, 'roles' => 'ROLE_TEACHER'),
            array('createdTime' => 'DESC'),
            0,
            10
        );

        $teachers = array();

        foreach ($users as $user) {
            $teachers[] = array(
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'avatar' => $this->getWebExtension()->getFilePath($user['smallAvatar'], 'avatar.png'),
                'isVisible' => 1,
            );
        }

        return $this->createJsonResponse($teachers);
    }

    public function studentsAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $fields = $request->query->all();
        $fields['userType'] = isset($fields['userType']) ? $fields['userType'] : 'login';

        $condition = array('courseId' => $course['id'], 'role' => 'student');

        if ($fields['userType'] == 'login') {
            $condition['userIdGT'] = 0;
        } elseif ($fields['userType'] == 'unlogin') {
            $condition['userId'] = 0;
        }

        if (isset($fields['isNotified']) && $fields['isNotified'] == 1) {
            $condition['isNotified'] = 1;
        }

        if (isset($fields['keyword']) && !empty($fields['keyword'])) {
            $users = $this->getUserService()->searchUsers(
                array('nickname' => $fields['keyword']),
                array('createdTime' => 'DESC'),
                0,
                PHP_INT_MAX
            );
            $userIds = ArrayToolkit::column($users, 'id');
            $condition['userIds'] = $userIds ? $userIds : array(-1);
        }

        $paginator = new Paginator(
            $request,
            $this->getOpenCourseService()->countMembers($condition),
            20
        );

        $students = $this->getOpenCourseService()->searchMembers(
            $condition,
            array('lastEnterTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $studentUserIds = ArrayToolkit::column($students, 'userId');
        $users = $this->getUserService()->findUsersByIds($studentUserIds);

        return $this->render(
            'open-course-manage/students.html.twig',
            array(
                'course' => $course,
                'students' => $students,
                'users' => $users,
                'paginator' => $paginator,
            )
        );
    }

    public function liveOpenTimeSetAction(Request $request, $id)
    {
        $liveCourse = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $openLiveLesson = $this->getOpenCourseService()->searchLessons(
            array('courseId' => $liveCourse['id']),
            array('startTime' => 'DESC'),
            0,
            1
        );
        $liveLesson = $openLiveLesson ? $openLiveLesson[0] : array();

        if ($request->getMethod() == 'POST') {
            $liveLessonFields = $request->request->all();

            if (!isset($liveLessonFields['startTime']) || empty($liveLessonFields['startTime'])) {
                return $this->createMessageResponse('error', '请先设置直播时间。');
            }

            $liveLesson['type'] = 'liveOpen';
            $liveLesson['courseId'] = $liveCourse['id'];
            $liveLesson['startTime'] = strtotime($liveLessonFields['startTime']);
            $liveLesson['length'] = $liveLessonFields['timeLength'];
            $liveLesson['title'] = $liveCourse['title'];

            if ($openLiveLesson) {
                $live = $this->getLiveCourseService()->editLiveRoom($liveCourse, $liveLesson, $this->container);
                $liveLesson = $this->getOpenCourseService()->updateLesson(
                    $liveLesson['courseId'],
                    $liveLesson['id'],
                    $liveLesson
                );
            } else {
                $live = $this->getLiveCourseService()->createLiveRoom($liveCourse, $liveLesson, $this->container);

                $liveLesson['mediaId'] = $live['id'];
                $liveLesson['liveProvider'] = $live['provider'];

                $liveLesson = $this->getOpenCourseService()->createLesson($liveLesson);
            }

            $this->setFlashMessage('success', 'site.save.success');
        }

        return $this->render(
            'open-course-manage/live-open-time-set.html.twig',
            array(
                'course' => $liveCourse,
                'openLiveLesson' => $liveLesson,
            )
        );
    }

    /**
     * @return LiveCourseService
     */
    protected function getLiveCourseService()
    {
        return $this->getBiz()->service('OpenCourse:LiveCourseService');
    }

    public function marketingAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        if ($request->getMethod() == 'POST') {
            $recommendIds = $request->request->get('recommendIds');

            $this->getOpenCourseRecommendedService()->updateOpenCourseRecommendedCourses($id, $recommendIds);

            $this->setFlashMessage('success', 'site.save.success');

            return $this->redirect(
                $this->generateUrl(
                    'open_course_manage_marketing',
                    array(
                        'id' => $id,
                    )
                )
            );
        }

        $recommends = $this->getOpenCourseRecommendedService()->findRecommendedCoursesByOpenCourseId($id);

        $courseSetIds = ArrayToolkit::column($recommends, 'recommendCourseId');
        $commendedCourseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        $recommendedCourses = array();
        foreach ($recommends as $key => $recommend) {
            //if recommendedCourse has been deleted  when do not show it or will make a error
            if (isset($commendedCourseSets[$recommend['recommendCourseId']])) {
                $recommendedCourses[$recommend['id']] = $commendedCourseSets[$recommend['recommendCourseId']];
            }
        }

        $users = $this->_getTeacherUsers($commendedCourseSets);

        return $this->render(
            'open-course-manage/open-course-marketing.html.twig',
            array(
                'courseSets' => $recommendedCourses,
                'users' => $users,
                'course' => $course,
            )
        );
    }

    /**
     * @return OpenCourseRecommendedService
     */
    protected function getOpenCourseRecommendedService()
    {
        return $this->getBiz()->service('OpenCourse:OpenCourseRecommendedService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->getBiz()->service('Course:CourseSetService');
    }

    protected function _findCoursesPriceInterval($courseSetIds)
    {
        if (empty($courses)) {
            return array();
        }

        return $this->getCourseService()->findPriceIntervalByCourseSetIds($courseSetIds);
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    protected function _getTeacherUsers(array $courses)
    {
        $teachers = ArrayToolkit::column($courses, 'teacherIds');

        if (empty($teachers)) {
            return array();
        }

        $userIds = call_user_func_array('array_merge', $teachers);

        $creators = ArrayToolkit::column($courses, 'creator');
        $userIds = array_merge($userIds, $creators);

        return $this->getUserService()->findUsersByIds($userIds);
    }

    public function pickAction(Request $request, $filter, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $conditions = $request->query->all();

        list($paginator, $courseSets) = $this->_getPickCourseData($request, $id, $conditions);

        $courseSetIds = ArrayToolkit::column($courseSets, 'id');
        $coursesPrice = $this->_findCoursesPriceInterval($courseSetIds);

        $users = $this->_getTeacherUsers($courseSets);

        return $this->render(
            'open-course-manage/open-course-pick-modal.html.twig',
            array(
                'users' => $users,
                'courseSets' => $courseSets,
                'coursesPrice' => $coursesPrice,
                'paginator' => $paginator,
                'courseId' => $id,
                'filter' => $filter,
            )
        );
    }

    protected function _getPickCourseData(Request $request, $openCourseId, $conditions)
    {
        $existRecommendCourseIds = $this->getExistRecommendCourseIds($openCourseId);

        $conditions = $this->_filterConditions($conditions, $existRecommendCourseIds);

        $paginator = new Paginator(
            $request,
            $this->getCourseSetService()->countCourseSets($conditions),
            5
        );

        $courseSets = $this->getCourseSetService()->searchCourseSets(
            $conditions,
            array('createdTime' => 'ASC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return array($paginator, $courseSets);
    }

    protected function getExistRecommendCourseIds($openCourseId)
    {
        $coursesRecommended = $this->getOpenCourseRecommendedService()->searchRecommends(
            array('openCourseId' => $openCourseId),
            array('createdTime' => 'DESC'),
            0,
            PHP_INT_MAX
        );

        $existIds = ArrayToolkit::column($coursesRecommended, 'recommendCourseId');

        return $existIds;
    }

    protected function _filterConditions($conditions, $excludeCourseIds)
    {
        $conditions['status'] = 'published';
        $conditions['parentId'] = 0;

        if (!empty($excludeCourseIds)) {
            $conditions['excludeIds'] = $excludeCourseIds;
        }

        if (isset($conditions['title']) && $conditions['title'] == '') {
            unset($conditions['title']);
        }

        return $conditions;
    }

    public function deleteRecommendCourseAction(Request $request, $id, $recommendId)
    {
        $this->getOpenCourseService()->tryManageOpenCourse($id);
        $this->getOpenCourseRecommendedService()->deleteRecommendCourse($recommendId);

        return $this->createJsonResponse(true);
    }

    public function searchAction(Request $request, $id, $filter)
    {
        $this->getOpenCourseService()->tryManageOpenCourse($id);
        $key = $request->query->get('key');
        $conditions = array('title' => $key);
        list($paginator, $courseSets) = $this->_getPickCourseData($request, $id, $conditions);

        $users = $this->_getTeacherUsers($courseSets);

        return $this->render(
            'open-course-manage/open-course-pick-modal.html.twig',
            array(
                'users' => $users,
                'courseSets' => $courseSets,
                'filter' => $filter,
                'courseId' => $id,
                'title' => $key,
                'paginator' => $paginator,
            )
        );
    }

    public function recommendedCoursesSelectAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $recommendNum = $this->getOpenCourseRecommendedService()->countRecommends(array('openCourseId' => $id));

        $ids = $request->request->get('ids');

        if (empty($ids)) {
            return $this->createJsonResponse(array('result' => true));
        }

        if (($recommendNum + count($ids)) > 5) {
            return $this->createJsonResponse(array('result' => false, 'message' => '推荐课程数量不能超过5个！'));
        }

        $this->getOpenCourseRecommendedService()->addRecommendedCourses($id, $ids, 'normal');

        return $this->createJsonResponse(array('result' => true));
    }

    public function publishAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $result = $this->getOpenCourseService()->publishCourse($id);

        if ($course['type'] == 'liveOpen' && !$result['result']) {
            $result['message'] = '请先设置直播时间';
        }

        if ($course['type'] == 'open' && !$result['result']) {
            $result['message'] = '请先创建课时';
        }

        return $this->createJsonResponse($result);
    }

    public function studentsExportDatasAction(Request $request, $id)
    {
        list($start, $limit, $exportAllowCount) = ExportHelp::getMagicExportSetting($request);

        list($title, $students, $courseMemberCount) = $this->getExportContent(
            $request,
            $id,
            $start,
            $limit,
            $exportAllowCount
        );

        $file = '';
        if ($start == 0) {
            $file = ExportHelp::addFileTitle($request, 'open-course-students', $title);
        }

        $content = implode("\r\n", $students);
        $file = ExportHelp::saveToTempFile($request, $content, $file);
        $status = ExportHelp::getNextMethod($start + $limit, $courseMemberCount);

        return $this->createJsonResponse(
            array(
                'status' => $status,
                'fileName' => $file,
                'start' => $start + $limit,
            )
        );
    }

    protected function getExportContent($request, $id, $start, $limit, $exportAllowCount)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);
        $gender = array('female' => '女', 'male' => '男', 'secret' => '秘密');
        $conditions = array('courseId' => $course['id'], 'role' => 'student');
        $userType = $request->query->get('userType', '');
        if ($userType == 'login') {
            $conditions['userIdGT'] = 0;
        } elseif ($userType == 'unlogin') {
            $conditions['userId'] = 0;
        }

        if ($request->query->get('isNotified', 0) == 1) {
            $conditions['isNotified'] = 1;
        }

        $courseMemberCount = $this->getOpenCourseService()->countMembers($conditions);
        $courseMemberCount = ($courseMemberCount > $exportAllowCount) ? $exportAllowCount : $courseMemberCount;
        if ($courseMemberCount < ($start + $limit + 1)) {
            $limit = $courseMemberCount - $start;
        }
        $courseMembers = $this->getOpenCourseService()->searchMembers(
            $conditions,
            array('createdTime' => 'DESC'),
            $start,
            $limit
        );
        $userFields = $this->getUserFieldService()->getEnabledFieldsOrderBySeq();

        $fields['weibo'] = '微博';

        foreach ($userFields as $userField) {
            $fields[$userField['fieldName']] = $userField['title'];
        }

        $studentUserIds = ArrayToolkit::column($courseMembers, 'userId');

        $users = $this->getUserService()->findUsersByIds($studentUserIds);
        $users = ArrayToolkit::index($users, 'id');

        $profiles = $this->getUserService()->findUserProfilesByIds($studentUserIds);
        $profiles = ArrayToolkit::index($profiles, 'id');

        $progresses = array();

        $str = '用户名,Email,手机号,加入学习时间,上次进入时间,IP,姓名,性别,QQ号,微信号,公司,职业,头衔';

        foreach ($fields as $key => $value) {
            $str .= ','.$value;
        }

        $students = array();

        foreach ($courseMembers as $courseMember) {
            $member = '';

            if ($userType == 'login') {
                $member .= $users[$courseMember['userId']]['nickname'].',';
                $member .= $users[$courseMember['userId']]['email'].',';
                $member .= $users[$courseMember['userId']]['verifiedMobile'] ? $users[$courseMember['userId']]['verifiedMobile'].',' : '-,';
                $member .= date('Y-n-d H:i:s', $courseMember['createdTime']).',';
                $member .= date('Y-n-d H:i:s', $courseMember['lastEnterTime']).',';
                $member .= $courseMember['ip'].',';
                $member .= $profiles[$courseMember['userId']]['truename'] ? $profiles[$courseMember['userId']]['truename'].',' : '-'.',';
                $member .= $gender[$profiles[$courseMember['userId']]['gender']].',';
                $member .= $profiles[$courseMember['userId']]['qq'] ? $profiles[$courseMember['userId']]['qq'].',' : '-'.',';
                $member .= $profiles[$courseMember['userId']]['weixin'] ? $profiles[$courseMember['userId']]['weixin'].',' : '-'.',';
                $member .= $profiles[$courseMember['userId']]['company'] ? $profiles[$courseMember['userId']]['company'].',' : '-'.',';
                $member .= $profiles[$courseMember['userId']]['job'] ? $profiles[$courseMember['userId']]['job'].',' : '-'.',';
                $member .= $users[$courseMember['userId']]['title'] ? $users[$courseMember['userId']]['title'].',' : '-'.',';

                foreach ($fields as $key => $value) {
                    $member .= $profiles[$courseMember['userId']][$key] ? $profiles[$courseMember['userId']][$key].',' : '-'.',';
                }
            } else {
                $member .= '-,-,';
                $member .= $courseMember['mobile'] ? $courseMember['mobile'].',' : '-,';
                $member .= date('Y-n-d H:i:s', $courseMember['createdTime']).',';
                $member .= date('Y-n-d H:i:s', $courseMember['lastEnterTime']).',';
                $member .= $courseMember['ip'].',';
                $member .= '-,-,-,-,-,-,-,';
                $member .= str_repeat('-,', count($fields) - 1).'-,';
            }

            $students[] = $member;
        }

        return array($str, $students, $courseMemberCount);
    }

    /**
     * @return UserFieldService
     */
    protected function getUserFieldService()
    {
        return $this->getBiz()->service('User:UserFieldService');
    }

    public function studentsExportAction(Request $request, $id)
    {
        $fileName = sprintf('open-course-%s-students-(%s).csv', $id, date('Y-n-d'));

        return ExportHelp::exportCsv($request, $fileName);
    }

    public function studentDetailAction(Request $request, $id, $userId)
    {
        if (!$this->getCurrentUser()->isAdmin()) {
            throw $this->createAccessDeniedException('您无权查看学员详细信息！');
        }

        $user = $this->getUserService()->getUser($userId);
        $profile = $this->getUserService()->getUserProfile($userId);
        $profile['title'] = $user['title'];

        $userFields = $this->getUserFieldService()->getEnabledFieldsOrderBySeq();

        for ($i = 0; $i < count($userFields); ++$i) {
            if (strstr($userFields[$i]['fieldName'], 'textField')) {
                $userFields[$i]['type'] = 'text';
            }

            if (strstr($userFields[$i]['fieldName'], 'varcharField')) {
                $userFields[$i]['type'] = 'varchar';
            }

            if (strstr($userFields[$i]['fieldName'], 'intField')) {
                $userFields[$i]['type'] = 'int';
            }

            if (strstr($userFields[$i]['fieldName'], 'floatField')) {
                $userFields[$i]['type'] = 'float';
            }

            if (strstr($userFields[$i]['fieldName'], 'dateField')) {
                $userFields[$i]['type'] = 'date';
            }
        }

        return $this->render(
            'open-course-manage/student-detail-modal.html.twig',
            array(
                'user' => $user,
                'profile' => $profile,
                'userFields' => $userFields,
            )
        );
    }

    public function lessonTimeCheckAction(Request $request, $courseId)
    {
        $data = $request->query->all();

        $startTime = $data['startTime'];
        $length = $data['length'];
        $lessonId = empty($data['lessonId']) ? '' : $data['lessonId'];

        list($result, $message) = $this->getOpenCourseService()->liveLessonTimeCheck(
            $courseId,
            $lessonId,
            $startTime,
            $length
        );

        if ($result == 'success') {
            $response = array('success' => true, 'message' => '这个时间段的课时可以创建');
        } else {
            $response = array('success' => false, 'message' => $message);
        }

        return $this->createJsonResponse($response);
    }

    protected function _getType($filter)
    {
        $type = 'open';

        if ($filter == 'openCourse') {
            $type = 'open';
        } elseif ($filter == 'otherCourse' || $filter == 'normal') {
            $type = 'normal';
        }

        return $type;
    }

    /**
     * @param  $type
     *
     * @return mixed
     */
    protected function getTypeCourseService($type)
    {
        return CourseProcessorFactory::create($type);
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }
}
