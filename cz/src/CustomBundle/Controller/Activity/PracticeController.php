<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Common\ImgConverToData;
use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Activity\ActivityActionInterface;

class PracticeController extends BaseController
{
    public function showAction(Request $request, $activity, $task, $mode)
    {
        $practice = $this->getActivityService()->getActivityConfig($activity['mediaType'])->get($activity['mediaId']);
        $materials = $this->getMaterialService()->findMaterialsByLessonId($activity['id']);
        $materials = $this->getMaterialType($materials);

        $course = $this->getCourseService()->getCourse($task['courseId']);
        $results = $this->buildResults($task);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        return $this->render('activity/practice/show/base.html.twig', array(
            'materials' => $materials,
            'activity' => $activity,
            'task' => $task,
            'lesson' => $lesson,
            'course' => $course,
            'results' => $results,
            'mode' => $mode,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id, $fetchMedia = true);
        $materials = $this->getMaterialService()->findMaterialsByLessonId($activity['id']);

        foreach ($materials as $material) {
            $id = empty($material['fileId']) ? $material['link'] : $material['fileId'];
            $activity['ext']['materials'][$id] = array('id' => $material['fileId'], 'size' => $material['fileSize'], 'name' => $material['title'], 'link' => $material['link']);
        }

        return $this->render('activity/practice/modal.html.twig', array(
            'activity' => $activity,
            'courseId' => $courseId,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/practice/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function previewAction(Request $request, $task)
    {
        $course = $this->getCourseService()->getCourse($task['courseId']);
        $activity = $this->getActivityService()->getActivity($task['activityId'], $fetchMedia = true);
        $download = $this->getActivityService()->getActivityConfig($activity['mediaType'])->get($activity['mediaId']);
        $materials = $this->getMaterialService()->findMaterialsByLessonId($activity['id']);

        return $this->render('activity/practice/preview.html.twig', array(
            'course' => $course,
            'materials' => $materials,
            'activity' => $activity,
            'download' => $download,
            'task' => $task,
        ));
    }

    public function loadResultAction($taskId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $task = $this->getTaskService()->getTask($taskId);

        $results = $this->buildResults($task, $task);

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        return $this->render("activity/practice/result.html.twig", array(
            'activity' => $activity,
            'results' => $results,
            'lesson' => $lesson,
            'task' => $task,
        ));
    }

    protected function buildResults($task)
    {
        $user = $this->getCurrentUser();
        $results = $this->getPracticeResultService()->findResultsByTaskId($task['id']);

        $resultIds = ArrayToolkit::column($results, 'id');
        $contents = $this->getPracticeResultService()->findContentsByResultIds($resultIds);
        $contents = $this->getContentThumb($contents);

        $contents = ArrayToolkit::index($contents, 'resultId');
        $contentIds = ArrayToolkit::column($contents, 'id');

        $userIds = ArrayToolkit::column($results, 'userId');
        $contentUserIds = ArrayToolkit::column($contents, 'userId');
        $userIds = array_merge($userIds, $contentUserIds);
        $users = $this->getUserService()->findUsersByIds($userIds);

        $likes = $this->getPracticeResultService()->findLikesByContentIdsAndUserId($contentIds, $user['id']);
        foreach ($results as $key => $result) {
            $results[$key]['truename'] = $users[$contents[$result['id']]['userId']]['truename'];
            $results[$key]['number'] = $users[$contents[$result['id']]['userId']]['number'];
            $results[$key]['avatar'] = $this->getWebExtension()->getFilePath($users[$contents[$result['id']]['userId']]['smallAvatar'], 'avatar.png');
            $results[$key]['content'] = $contents[$result['id']];
            $results[$key]['isStar'] = empty($likes[$contents[$result['id']]['id']]) ? 0 : 1;
            $results[$key]['isTeacher'] = $result['isTeacher'];
        }

        $results = ArrayToolkit::group($results, 'isTeacher');

        return $results;
    }

    public function pictureShowAction($contentId)
    {
        $content = $this->getPracticeResultService()->getContent($contentId);
        $uri = $this->getWebExtension()->parseFileUri($content['uri']);

        $path = rtrim($this->container->getParameter('topxia.upload.private_directory'), ' /').'/'.$uri['path'];

        $imgConverToData = new ImgConverToData();
        $imgConverToData->getImgDir($path);
        $imgConverToData->img2Data();
        $imgData = $imgConverToData->data2Img();

        echo $imgData;
        exit();
    }

    public function remarkAction(Request $request, $resultId)
    {
        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();
            $result = $this->getPracticeResultService()->remark($resultId, $fields);

            return $this->createJsonResponse(true);
        }
        $result = $this->getPracticeResultService()->getResult($resultId);
        $user = $this->getUserService()->getUser($result['userId']);

        return $this->render('activity/remark.html.twig', array(
            'remarkPath' => 'practice_result_remark',
            'result' => $result,
            'user' => $user,
        ));
    }

    public function contentShowAction($contentId)
    {
        $content = $this->getPracticeResultService()->getContent($contentId);

        $posts = $this->getPracticeResultService()->findPostsByContentId($contentId);
        $userIds = ArrayToolkit::column($posts, 'userId');
        $userIds = array_merge($userIds, array($content['userId']));
        $users = $this->getUserService()->findUsersByIds($userIds);
        foreach ($users as $key => &$user) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $user['avatar'] = $this->getWebExtension()->getFilePath($avatar, 'avatar.png');
        }
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);
        $isLike = $this->getPracticeResultService()->isLike($contentId);

        $result = $this->getPracticeResultService()->getResult($content['resultId']);
        $results = $this->getPracticeResultService()->findResultsByActivityIdAndIsTeacher($result['activityId'], $result['isTeacher']);
        $resultIds = ArrayToolkit::column($results, 'id');
        $contents = $this->getPracticeResultService()->findContentsByResultIds($resultIds);
        $switch = $this->showSwitch($contentId, $contents);

        $contents = $this->getContentThumb($contents);
        $contents = ArrayToolkit::index($contents, 'id');

        return $this->render('activity/practice/detail.html.twig', array(
            'result' => $result,
            'content' => $content,
            'posts' => $posts,
            'users' => $users,
            'profiles' => $profiles,
            'isLike' => $isLike,
            'contents' => array_values($contents),
            'switch' => $switch,
        ));
    }

    public function showSwitch($contentId, $contents)
    {
        $contents = array_values($contents);
        foreach ($contents as $index => $content) {
            if ($contentId == $content['id']) { 
                return $index;
            }     
        }

        return false;
    }

    public function postContentAction(Request $request, $contentId)
    {
        $fields = $request->request->all();
        $fields['contentId'] = $contentId;

        $post = $this->getPracticeResultService()->createPost($fields);
        $userIds = array($post['userId'], $post['parentId']);
        $users = $this->getUserService()->findUsersByIds($userIds);

        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $result = array(
            'avatar' => $this->userAvatar($users[$post['userId']]['smallAvatar']),
            'name' => empty($profiles[$post['userId']]['truename']) ? $users[$post['userId']]['nickname'] : $profiles[$post['userId']]['truename'],
            'comment' => $post['content'],
            'replyName' => null,
            'date' => $post['createdTime'],
        );

        if (!empty($post['parentId'])) {
            $result['replyName'] = empty($profiles[$post['parentId']]['truename']) ? $users[$post['parentId']]['nickname'] : $profiles[$post['parentId']]['truename'];
        }

        return $this->createJsonResponse($result);
    }

    public function likeAction($contentId)
    {
        $this->getPracticeResultService()->like($contentId);

        return $this->createJsonResponse(true);
    }

    public function cancelLikeAction($contentId)
    {
        $this->getPracticeResultService()->cancelLike($contentId);

        return $this->createJsonResponse(true);
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/download/finish-condition.html.twig', array());
    }

    protected function getContentThumb($contents)
    {
        foreach ($contents as &$content) {
            $content['thumb'] = $this->getWebExtension()->getFilePath($content['uri'], '');
        }

        return $contents;
    }

    protected function getMaterialType($materials)
    {
        $fileIds = ArrayToolkit::column($materials, 'fileId');
        $files = $this->getUploadFileService()->findFilesByIds($fileIds);
        $files = ArrayToolkit::index($files, 'id');

        foreach ($materials as $key => &$material) {
            if (empty($files[$material['fileId']])) {
                unset($materials[$key]);
                continue;
            }
            $material['type'] = $files[$material['fileId']]['type'];
        }

        return $materials;
    }

    public function saveToMaterialAction($contentId)
    {
        $this->getUploadFileService()->addActivityPracticeFile($contentId);

        return $this->createJsonResponse(true);
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return MaterialService
     */
    protected function getMaterialService()
    {
        return $this->createService('CustomBundle:Course:MaterialService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getPracticeResultService()
    {
        return $this->createService('CustomBundle:Practice:PracticeResultService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getUploadFileService()
    {
        return $this->createService('CustomBundle:File:UploadFileService');
    }
}
