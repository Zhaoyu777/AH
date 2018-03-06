<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\FileToolkit;
use AppBundle\Common\ExportHelp;

class PracticeWorkController extends BaseController
{
    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/practice-work/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function showPracticeWorkModalAction(Request $request, $practiceWorkResultId)
    {
        $practiceWorkResult = $this->getPracticeWorkService()->getResult($practiceWorkResultId);
        if ($practiceWorkResult['status'] == 'create') {
            $PracticeWorkResult = $this->getPracticeWorkService()->updateResult($practiceWorkResultId, array('status' => 'reviewing'));
        }
        if (!$practiceWorkResult) {
            throw $this->NotFoundException("practiceWorkResult#{$practiceWorkResultId} Not Found");
        }
        
        $activity = $this->getActivityService()->getActivity($practiceWorkResult['activityId']);
        $file = $this->getFile($request, $practiceWorkResult);
        
        return $this->render('course-manage/homework-check/result-list-modal.html.twig', array(
                'file' => $file,
                'practiceWorkResult' => $practiceWorkResult,
                'activity' => $activity
            ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('practiceWork');
        $practiceWork = $config->get($activity['mediaId']);
        return $this->render('activity/practice-work/modal.html.twig', array(
            'activity' => $activity,
            'courseId' => $activity['fromCourseId'],
            'practiceWork' => $practiceWork,
            'courseSetId' => $course['courseSetId'],
        ));
    }

    public function downloadWeixinFileAction(Request $request, $fileId)
    {
        $file = $this->getFileService()->getFile($fileId);
        $path = $this->getUri($file['uri']);

        $response = BinaryFileResponse::create($path, 200, array(), false);
        $response->trustXSendfileTypeHeader();

        $str = explode('/', $file['uri']);
        $fileName = $str[count($str) - 1];

        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName."; filename*=UTF-8''".$fileName);

        $extString = explode('.', $fileName);

        $mimeType = FileToolkit::getMimeTypeByExtension($extString[count($extString) - 1]);

        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    private function getUri($uri)
    {
        $uri = $this->getWebExtension()->parseFileUri($uri);

        return rtrim($this->container->getParameter('topxia.upload.private_directory'), ' /').'/'.$uri['path'];
    }

    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/practice-work/preview.html.twig', array(
            'activity' => $activity,
            'task' => $task,
            'course' => $course,
        ));
    }

    public function showAction(Request $request, $task)
    {
        list($temp, $showArray) = $this->initShowData($request, $task);
        return $this->render($temp, $showArray);
    }

    private function initShowData($request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('practiceWork');
        $practiceWork = $config->get($activity['mediaId']);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user not login');
        }

        $course = $this->getCourseService()->getCourse($task['courseId']);

        $isTeacher = $this->getCourseMemberService()->isCourseTeacher($activity['fromCourseId'], $user['id']);

        if (!$isTeacher) {
            $practiceWorkResult = $this->getPracticeWorkService()->getResultByTaskIdAndUserId($task['id'], $user['id']);
            if ($practiceWorkResult) {
                $practiceWorkResult['file'] = $this->getFile($request, $practiceWorkResult);
            }

            return array('activity/practice-work/show/student.html.twig', array(
                'activity' => $activity,
                'task' => $task,
                'practiceWork' => $practiceWork,
                'practiceWorkResult' => $practiceWorkResult,
                'isTeacher' => $isTeacher,
                'course' => $course,
            ));
        }

        $practiceWorkResults = $this->getPracticeWorkService()->findPracticeWorkResultsByPracticeWorkId($practiceWork['id']);
        $practiceWorkResults = ArrayToolkit::index($practiceWorkResults, 'userId');
        foreach ($practiceWorkResults as &$result) {
            if ($result['origin'] == 'weixin') {
                $file = $this->getFileService()->getFile($result['fileId']);
            } else {
                $file = $this->getUploadFileService()->getFile($result['fileId']);
            }
            $pictureUrl = $this->getPictureUrl($request, $result, $file);
            $result['url'] = empty($pictureUrl) ? null:$pictureUrl;
        }

        $conditions = array('courseId' => $activity['fromCourseId'], 'role' => 'student');

        $totalMemberCount = $this->getCourseMemberService()->countMembers($conditions);

        $studentUserIds = ArrayToolkit::column($practiceWorkResults, 'userId');

        $users = $this->getUserService()->findUsersByIds($studentUserIds);
        $users = ArrayToolkit::index($users, 'id');

        $profiles = $this->getUserService()->findUserProfilesByIds($studentUserIds);
        $profiles = ArrayToolkit::index($profiles, 'id');
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        $status = $this->getStatusService()->getStatusByTaskId($task['id']);

        return array('activity/practice-work/show/teacher.html.twig', array(
            'activity' => $activity,
            'task' => $task,
            'practiceWork' => $practiceWork,
            'practiceWorkResults' => $practiceWorkResults,
            'isTeacher' => $isTeacher,
            'totalMemberCount' => $totalMemberCount,
            'realityMemberCount' => count($practiceWorkResults),
            'users' => $users,
            'profiles' => $profiles,
            'lesson' => $lesson,
            'taskStatus' => $status,
            'lessonTask' => $lessonTask,
            'course' => $course,
        ));
    }

    private function getPictureUrl($request, $result, $file)
    {
        $ssl = $request->isSecure() ? true : false;
        if (!empty($result) && !empty($file)) {
            return ($ssl ? 'https://' : 'http://').$request->getHost().$this->generateUrl('weixin_practice_work_picture_show', array('type' => $result['origin'], 'id' => $file['id']));
        }
        
        if (isset($file['storage']) && $file['storage'] == 'cloud' && $file['type'] == 'image') {
            $ssl = $request->isSecure() ? true : false;
            $file = $this->getMaterialLibService()->player($file['globalId'], $ssl);
            return $file['preview'];
        }
    }

    public function createResultAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if ($request->getMethod() === 'POST') {
            $formData = $request->request->all();

            $savedResult = $this->getPracticeWorkService()->getResultByTaskIdAndUserId($formData['taskId'], $user['id']);
            if ($savedResult) {
                if ($savedResult['status'] == 'reviewing') {
                    return $this->createJsonResponse(array('result' => false, 'message' => '老师正在批阅中，不能再上传'));
                }
                $data['fileId'] = $formData['fileId'];
                $data['finalSubTime'] = time();
                $data['origin'] = 'pc';
                $result = $this->getPracticeWorkService()->updateResult($savedResult['id'], $data);
            } else {
                $formData['userId'] = $user['id'];
                $formData['finalSubTime'] = time();
                $result = $this->getPracticeWorkService()->createResult($formData);
            }

            $response = array('result' => true, 'message' => '');

            return $this->createJsonResponse($response);
        }
    }

    public function teacherReviewAction(Request $request, $practiceWorkResultId)
    {
        $user = $this->getCurrentUser();
        if ($request->getMethod() === 'POST') {
            $formData = $request->request->all();
            $data['appraisal'] = $formData['appraisal'];
            $data['comment'] = empty($formData['comment']) ? null:$formData['comment'];
            $data['status'] = 'finished';
            $data['checkTeacherId'] = $user['id'];
            $data['reviewTime'] = time();
            $result = $this->getPracticeWorkService()->reviewResult($practiceWorkResultId, $data);

            $response = array('result' => true, 'message' => '');

            return $this->createJsonResponse(true);
        }
    }

    public function playerAction(Request $request, $practiceWorkResultId, $fileId)
    {
        $practiceWorkResult = $this->getPracticeWorkService()->getResult($practiceWorkResultId);
        if (!$practiceWorkResult) {
            throw $this->NotFoundException("PracticeWorkResult#{$practiceWorkResultId} Not Found");
        }
        
        $activity = $this->getActivityService()->getActivity($practiceWorkResult['activityId']);
        $file = $this->tryAccessFile($activity['fromCourseId'], $fileId);

        if ($file['storage'] == 'cloud') {
            return $this->forward('AppBundle:MaterialLib/GlobalFilePlayer:player', array(
                'request' => $request,
                'globalId' => $file['globalId'],
            ));
        }

        return $this->render('material-lib/web/local-player.html.twig', array());
    }

    public function exportResultDatasAction(Request $request, $practiceWorkId)
    {
        list($start, $limit, $exportAllowCount) = ExportHelp::getMagicExportSetting($request);
        list($title, $students, $courseMemberCount) = $this->getExportContent(
            $request,
            $practiceWorkId,
            $start,
            $limit,
            $exportAllowCount
        );

        $file = '';
        if ($start == 0) {
            $file = ExportHelp::addFileTitle($request, 'practice_work_result', $title);
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

    protected function getExportContent($request, $practiceWorkId, $start, $limit, $exportAllowCount)
    {

        $activity = $this->getActivityService()->getActivityByMediaIdAndMediaType($practiceWorkId, 'practiceWork');
        $conditions = array('courseId' => $activity['fromCourseId'], 'role' => 'student');
        $practiceWorkResults = $this->getPracticeWorkService()->findPracticeWorkResultsByPracticeWorkId($practiceWorkId);
        $practiceWorkResults = ArrayToolkit::index($practiceWorkResults, 'userId');

        $courseMemberCount = $this->getCourseMemberService()->countMembers($conditions);
        $courseMemberCount = ($courseMemberCount > $exportAllowCount) ? $exportAllowCount : $courseMemberCount;
        if ($courseMemberCount < ($start + $limit + 1)) {
            $limit = $courseMemberCount - $start;
        }
        $courseMembers = $this->getCourseMemberService()->searchExportMembers(
            $conditions,
            $start,
            $limit
        );

        $studentUserIds = ArrayToolkit::column($courseMembers, 'userId');

        $users = $this->getUserService()->findUsersByIds($studentUserIds);
        $users = ArrayToolkit::index($users, 'id');

        $profiles = $this->getUserService()->findUserProfilesByIds($studentUserIds);
        $profiles = ArrayToolkit::index($profiles, 'id');

        $appraisal = array(
            '1' => '优秀',
            '2' => '良好',
            '3' => '一般',
            '4' => '合格',
            '5' => '不合格',
        );

        $str = '姓名,学号,评价,作业提交时间';

        $students = array();

        foreach ($courseMembers as $courseMember) {
            $member = '';

            $member .= $profiles[$courseMember['userId']]['truename'] ? $profiles[$courseMember['userId']]['truename'].',' : '-'.',';
            $member .= $users[$courseMember['userId']]['number'].',';
            $member .= !empty($practiceWorkResults[$courseMember['userId']]['appraisal']) ? $appraisal[$practiceWorkResults[$courseMember['userId']]['appraisal']].',' : '/'.',';
            $member .= isset($practiceWorkResults[$courseMember['userId']]) ? date('Y-n-d H:i:s', $practiceWorkResults[$courseMember['userId']]['finalSubTime']).',' : '/'.',';

            $students[] = $member;
        }

        return array($str, $students, $courseMemberCount);
    }

    public function exportCsvAction(Request $request, $practiceWorkId)
    {
        $activity = $this->getActivityService()->getActivityByMediaIdAndMediaType($practiceWorkId, 'practiceWork');
        $name = $activity['title'];
        $fileName = sprintf("${name}-(%s).csv", date('Y-n-d'));

        return ExportHelp::exportCsv($request, $fileName);
    }

    private function getFile($request, $practiceWorkResult)
    {
        if ($practiceWorkResult['origin'] == 'pc') {
            $file = $this->getUploadFileService()->getFile($practiceWorkResult['fileId']);
            if (!empty($file['globalId'])) {
                $file = $this->getCloudFileService()->getByGlobalId($file['globalId']);
            }
            $pictureUrl = $this->getPictureUrl($request, $practiceWorkResult, $file);
            $file['url'] = empty($pictureUrl) ? null:$pictureUrl;

            return $file;
        }

        if ($practiceWorkResult['origin'] == 'weixin') {
            $file = $this->getFileService()->getFile($practiceWorkResult['fileId']);
            $str = explode('/', $file['uri']);
            $file['filename'] = $str[count($str) - 1];
            $file['type'] = 'image';

            $pictureUrl = $this->getPictureUrl($request, $practiceWorkResult, $file);
            $file['url'] = empty($pictureUrl) ? null:$pictureUrl;

            return $file;
        }
    }

    private function tryAccessFile($courseId, $fileId)
    {
        $file = $this->getUploadFileService()->getFullFile($fileId);

        if (empty($file)) {
            throw $this->NotFoundException("file not found");
        }

        $user = $this->getCurrentUser();

        if ($user->isAdmin()) {
            return $file;
        }
        $isTeacher = $this->getCourseMemberService()->isCourseTeacher($courseId, $user['id']);
        if ($isTeacher) {
            return $file;
        }
        throw $this->createAccessDeniedException('您无权访问此文件！');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getUpService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getPracticeWorkService()
    {
        return $this->createService('CustomBundle:Activity:PracticeWorkService');
    }

    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getCloudFileService()
    {
        return $this->createService('CloudFile:CloudFileService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    protected function getMaterialLibService()
    {
        return $this->createService('MaterialLib:MaterialLibService');
    }
}
