<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Biz\Activity\Strategy\StrategyContext;
use AppBundle\Controller\Activity\ActivityActionInterface;

class DisplayWallController extends BaseController
{
    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $displayWall = $config->get($activity['mediaId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/display-wall/preview.html.twig', array(
            'course' => $course,
            'task' => $task,
            'activity' => $activity,
            'displayWall' => $displayWall,
        ));
    }

    public function showAction(Request $request, $activity, $task, $mode)
    {
        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $displayWall = $config->get($activity['mediaId']);

        $groups = $this->buildResults($task, $displayWall);

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);

        return $this->render('activity/display-wall/show/base.html.twig', array(
            'activity' => $activity,
            'displayWall' => $displayWall,
            'groups' => $groups,
            'lesson' => $lesson,
            'course' => $course,
            'task' => $task,
            'mode' => $mode,
        ));
    }

    private function buildResults($task, $displayWall)
    {
        $user = $this->getCurrentUser();
        $results = $this->getResultService()->findResultsByTaskId($task['id']);

        $resultIds = ArrayToolkit::column($results, 'id');
        $contents = $this->getResultService()->findContentsByResultIds($resultIds);
        $contents = ArrayToolkit::index($contents, 'resultId');
        $contentIds = ArrayToolkit::column($contents, 'id');

        $userIds = ArrayToolkit::column($results, 'userId');
        $contentUserIds = ArrayToolkit::column($contents, 'userId');
        $userIds = array_merge($userIds, $contentUserIds);
        $users = $this->getUserService()->findUsersByIds($userIds);

        $likes = $this->getDisplayWallResultService()->findLikesByContentIdsAndUserId($contentIds, $user['id']);
        foreach ($results as $key => $result) {
            $results[$key]['truename'] = $users[$contents[$result['id']]['userId']]['truename'];
            $results[$key]['number'] = $users[$contents[$result['id']]['userId']]['number'];
            $results[$key]['content'] = $contents[$result['id']];
            $results[$key]['isStar'] = empty($likes[$contents[$result['id']]['id']]) ? 0 : 1;
        }

        $response = array();
        if ($displayWall['groupWay'] == 'none') {
            $response['results'] = $results;
            $response['memberCounts'] = $this->getTaskGroupService()->countTaskGroupMembersByTaskId($task['id']);
            $response['replyCounts'] = count($results);

            return $response;
        }

        if ($displayWall['submitWay'] == 'person') {
            $memberCounts = $this->getTaskGroupService()->countTaskGroupMembersByTaskIdGroupByGroupId($task['id']);
            $memberCounts = ArrayToolkit::index($memberCounts, 'groupId');
        }

        $results = ArrayToolkit::group($results, 'groupId');
        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId($task['id']);
        foreach ($groups as $key => $group) {
            $tmpGroup['id'] = $group['id'];
            $captain = $this->getTaskGroupService()->getGroupCaptainByGroupId($group['id']);
            if (!empty($captain)) {
                $user = $this->getUserService()->getUser($captain['userId']);
                $tmpGroup['captain'] = $user['truename'];
            }
            $tmpGroup['title'] = $group['title'];
            $tmpGroup['results'] = empty($results[$group['id']]) ? array() : $results[$group['id']];
            if ($displayWall['submitWay'] == 'person') {
                $tmpGroup['memberCount'] = empty($memberCounts[$group['id']]) ? 0 : $memberCounts[$group['id']]['count'];
                $tmpGroup['replyCount'] = count($tmpGroup['results']);
            }
            if (empty($defaultGroup)) {
                $defaultGroup = $tmpGroup;
            } else {
                $response[$key] = $tmpGroup;
            }
        }

        if (!empty($defaultGroup) && $displayWall['groupWay'] != 'random') {
            $response[] = $defaultGroup;
        }

        if (!empty($defaultGroup) && $displayWall['groupWay'] == 'random') {
            array_unshift($response, $defaultGroup);
        }

        return $response;
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $displayWall = $config->get($activity['mediaId']);

        return $this->render('activity/display-wall/modal.html.twig', array(
            'activity' => $activity,
            'displayWall' => $displayWall,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/display-wall/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/display-wall/finish-condition.html.twig', array());
    }

    public function startAction($taskId, $activityId)
    {
        $this->getStatusService()->startTask($taskId, $activityId);

        return $this->createJsonResponse(true);
    }

    public function endAction($taskId)
    {
        $this->getStatusService()->endTask($taskId);

        return $this->createJsonResponse(true);
    }

    public function remarkAction(Request $request, $resultId)
    {
        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();
            $result = $this->getResultService()->remark($resultId, $fields);

            return $this->createJsonResponse(true);
        }
        $result = $this->getResultService()->getResult($resultId);
        $user = $this->getUserService()->getUser($result['userId']);

        return $this->render('activity/remark.html.twig', array(
            'remarkPath' => 'display_wall_result_remark',
            'result' => $result,
            'user' => $user,
        ));
    }

    public function loadResultAction($taskId, $activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $displayWall = $config->get($activity['mediaId']);

        $task = $this->getTaskService()->getTask($taskId);

        $groups = $this->buildResults($task, $displayWall);

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if ($displayWall['groupWay'] == 'none') {
            $template = 'activity/display-wall/show/group-none.html.twig';
        } else {
            $template = 'activity/display-wall/show/submit-'.$displayWall['submitWay'].'.html.twig';
        }

        return $this->render($template, array(
            'activity' => $activity,
            'displayWall' => $displayWall,
            'groups' => $groups,
            'lesson' => $lesson,
            'task' => $task,
        ));
    }

    public function contentShowAction($contentId)
    {
        $content = $this->getResultService()->getContent($contentId);

        $posts = $this->getResultService()->findPostsByContentId($contentId);
        $userIds = ArrayToolkit::column($posts, 'userId');
        $userIds = array_merge($userIds, array($content['userId']));
        $users = $this->getUserService()->findUsersByIds($userIds);
        foreach ($users as $key => &$user) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $user['avatar'] = $this->getWebExtension()->getFilePath($avatar, 'avatar.png');
        }
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);
        $isLike = $this->getResultService()->isLike($contentId);

        $result = $this->getResultService()->getResult($content['resultId']);
        $results = $this->getResultService()->findResultsByActivityId($result['activityId']);
        $resultIds = ArrayToolkit::column($results, 'id');
        $contents = $this->getResultService()->findContentsByResultIds($resultIds);
        $contents = ArrayToolkit::index($contents, 'id');
        $switch = $this->showSwitch($contentId, $contents);

        return $this->render('activity/display-wall/detail.html.twig', array(
            'result' => $this->getResultService()->getResult($content['resultId']),
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

    public function likeAction($contentId)
    {
        $this->getDisplayWallResultService()->like($contentId);
        $content = $this->getDisplayWallResultService()->getContent($contentId);

        return $this->createJsonResponse($content['resultId']);
    }

    public function cancelLikeAction($contentId)
    {
        $this->getDisplayWallResultService()->cancelLike($contentId);
        $content = $this->getDisplayWallResultService()->getContent($contentId);

        return $this->createJsonResponse($content['resultId']);
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function createActivityGroupStrategy($activity, $displayWall)
    {
        return StrategyContext::getInstance()->createStrategy($displayWall, $this->get('biz'), $this->container);
    }

    protected function getDisplayWallResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }
}
