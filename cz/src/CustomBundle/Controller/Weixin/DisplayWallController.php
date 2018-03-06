<?php

namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use CustomBundle\Biz\Activity\Strategy\StrategyContext;
use Symfony\Component\HttpFoundation\Request;

class DisplayWallController extends BaseController
{
    public function resultAction($taskId)
    {
        $user = $this->getCurrentUser();
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);

        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $displayWall = $config->get($activity['mediaId']);

        $groups = $this->buildResults($user, $task, $displayWall);

        $hasGroup = true;
        if ($displayWall['groupWay'] == 'random') {
            $groupMember = $this->getTaskGroupService()->getTaskGroupMemberByUserIdAndTaskId($user['id'], $taskId);
            if (empty($groupMember)) {
                $hasGroup = false;
            }
        }

        return $this->createJsonResponse(array(
            'groups' => $groups,
            'status' => $this->getTaskStatus($taskId),
            'hasGroup' => $hasGroup,
            'groupWay' => $displayWall['groupWay'],
            'submitWay' => $displayWall['submitWay'],
        ));
    }

    private function getTaskStatus($taskId)
    {
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        return $status['status'];
    }

    protected function buildResults($user, $task, $displayWall)
    {
        $results = $this->getDisplayWallResultService()->findResultsByTaskId($task['id']);

        $resultIds = ArrayToolkit::column($results, 'id');
        $contents = $this->getDisplayWallResultService()->findContentsByResultIds($resultIds);
        foreach ($contents as $key => &$content) {
            $content['thumb'] = $this->get('web.twig.extension')->getFpath($content['uri']);
        }
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
            $results[$key]['avatar'] = $this->getWebExtension()->getFpath($users[$contents[$result['id']]['userId']]['smallAvatar'], 'avatar.png');
            $results[$key]['content'] = $contents[$result['id']];
            $results[$key]['isStar'] = empty($likes[$contents[$result['id']]['id']]) ? 0 : 1;
        }

        $response = array();
        if ($displayWall['groupWay'] == 'none') {
            $groupMembers = $this->getTaskGroupService()->findTaskGroupMembersByTaskId($task['id']);
            $groupMember = reset($groupMembers);
            $response['groupId'] = $groupMember['groupId'];
            $response['results'] = $results;
            $response['memberCounts'] = $this->getTaskGroupService()->countTaskGroupMembersByTaskId($task['id']);
            $response['replyCounts'] = count($results);

            return array($response);
        }

        $results = ArrayToolkit::group($results, 'groupId');

        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId($task['id']);
        $groupMembers = $this->getTaskGroupService()->findTaskGroupMembersByTaskId($task['id']);
        $userIds = ArrayToolkit::column($groupMembers, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        foreach ($groupMembers as $key => &$member) {
            $member = array(
                'userId' => $member['userId'],
                'groupId' => $member['groupId'],
                'avatar' => $this->getWebExtension()->getFpath($users[$member['userId']]['smallAvatar'], 'avatar.png'),
                'name' => $users[$member['userId']]['truename'],
            );
        }
        $groupMembers = ArrayToolkit::group($groupMembers, 'groupId');
        $index = 1;
        foreach ($groups as $key => $group) {
            $response[$key]['title'] = $group['title'];
            $response[$key]['no'] = "{$index}组";
            $response[$key]['groupId'] = $group['id'];

            if ($displayWall['submitWay'] == 'person') {
                $memberCounts = $this->getTaskGroupService()->countTaskGroupMembersByTaskIdGroupByGroupId($task['id']);
                $memberCounts = ArrayToolkit::index($memberCounts, 'groupId');
                $response[$key]['results'] = empty($results[$group['id']]) ? null : $results[$group['id']];
                $response[$key]['memberCount'] = empty($memberCounts[$group['id']]) ? 0 : $memberCounts[$group['id']]['count'];
                $response[$key]['replyCount'] = count($response[$key]['results']);
            } else {
                $response[$key]['members'] = array();
                if (!empty($groupMembers[$group['id']])) {
                    $response[$key]['members'] = $groupMembers[$group['id']];
                }
                $response[$key]['results'] = empty($results[$group['id']]) ? null : $results[$group['id']];
            }
            $index ++;
        }

        return $response;
    }

    public function contentShowAction($contentId)
    {
        $content = $this->getDisplayWallResultService()->getContent($contentId);
        $posts = $this->getDisplayWallResultService()->findPostsByContentId($contentId);

        $userIds = ArrayToolkit::column($posts, 'userId');
        $userIds[] = $content['userId'];
        $parentIds =  ArrayToolkit::column($posts, 'parentId');
        $userIds = array_merge($userIds, $parentIds);
        $users = $this->getUserService()->findUsersByIds($userIds);

        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);
        $contents = array();

        foreach ($posts as $post) {
            $postContent = array(
                'avatar' => $this->userAvatar($users[$post['userId']]['smallAvatar'], 'avatar.png'),
                'userId' => $post['userId'],
                'postId' => $post['id'],
                'name' => empty($profiles[$post['userId']]['truename']) ? $users[$post['userId']]['nickname'] : $profiles[$post['userId']]['truename'],
                'content' => $post['content'],
                'replyName' => null,
                'date' => $post['createdTime'],
            );
            if (!empty($post['parentId'])) {
                $postContent['replyName'] = empty($profiles[$post['parentId']]['truename']) ? $users[$post['parentId']]['nickname'] : $profiles[$post['parentId']]['truename'];
            }
            $contents[] = $postContent;
        }

        $content = array(
            'avatar' => $this->userAvatar($users[$content['userId']]['smallAvatar']),
            'name' => empty($profiles[$content['userId']]['truename']) ? $users[$content['userId']]['nickname'] : $profiles[$content['userId']]['truename'],
            'thumb' => $this->getWebExtension()->getFilePath($content['uri'], ''),
        );

        return $this->createJsonResponse(array(
            'content' => $content,
            'posts' => $contents,
        ));
    }

    public function postContentAction(Request $request, $contentId)
    {
        $fields = $request->query->all();
        $fields['contentId'] = $contentId;

        $post = $this->getDisplayWallResultService()->createPost($fields);
        $userIds = array($post['userId'], $post['parentId']);
        $users = $this->getUserService()->findUsersByIds($userIds);

        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $response = array(
            'avatar' => $this->userAvatar($users[$post['userId']]['smallAvatar']),
            'name' => empty($profiles[$post['userId']]['truename']) ? $users[$post['userId']]['nickname'] : $profiles[$post['userId']]['truename'],
            'content' => $post['content'],
            'replyName' => null,
            'date' => $post['createdTime'],
        );

        if (!empty($post['parentId'])) {
            $response['replyName'] = empty($profiles[$post['parentId']]['truename']) ? $users[$post['parentId']]['nickname'] : $profiles[$post['parentId']]['truename'];
        }

        return $this->createJsonResponse($response);
    }

    public function remarkAction(Request $request, $resultId, $groupWay, $submitWay)
    {
        $fields = $request->query->all();
        $fields['remark'] = explode(',', $fields['remark']);

        $result = $this->getDisplayWallResultService()->remark($resultId, $fields);

        return $this->createJsonResponse($fields['score']);
    }

    public function startAction($taskId, $activityId)
    {
        $status = $this->getStatusService()->startTask($taskId, $activityId);

        return $this->createJsonResponse($status['status']);
    }

    public function endAction($taskId)
    {
        $status = $this->getStatusService()->endTask($taskId);

        return $this->createJsonResponse($status['status']);
    }

    public function likeAction($contentId)
    {
        $this->getDisplayWallResultService()->like($contentId);

        return $this->createJsonResponse(true);
    }

    public function cancelLikeAction($contentId)
    {
        $this->getDisplayWallResultService()->cancelLike($contentId);

        return $this->createJsonResponse(true);
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

    public function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getDisplayWallResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    private function userAvatar($avatar)
    {
        return $this->getWebExtension()->getFpath($avatar, 'avatar.png');
    }

    protected function createActivityGroupStrategy($activity, $displayWall)
    {
        return StrategyContext::getInstance()->createStrategy($displayWall, $this->get('biz'), $this->container);
    }
}
