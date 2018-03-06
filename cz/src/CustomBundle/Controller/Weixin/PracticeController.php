<?php

namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Controller\Weixin\WeixinBaseController;
use CustomBundle\Biz\Activity\Strategy\StrategyContext;
use Symfony\Component\HttpFoundation\Request;

class PracticeController extends WeixinBaseController
{
    public function resultAction($taskId)
    {
        $user = $this->getCurrentUser();
        $task = $this->getTaskService()->getTask($taskId);

        $results = $this->buildResults($user, $task);

        return $this->createJsonResponse(array(
            'results' => $results, 
            'taskStatus' => $this->getTaskStatus($taskId),
        ));
    }

    private function getTaskStatus($taskId)
    {
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        return $status['status'];
    }

    protected function buildResults($user, $task)
    {
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
            $results[$key]['avatar'] = $this->getWebExtension()->getFpath($users[$contents[$result['id']]['userId']]['smallAvatar'], 'avatar.png');
            $results[$key]['content'] = $contents[$result['id']];
            $results[$key]['isStar'] = empty($likes[$contents[$result['id']]['id']]) ? 0 : 1;
        }

        return $results;
    }

    protected function getContentThumb($contents)
    {
        foreach ($contents as &$content) {
            $content['thumb'] = $this->getWebExtension()->getFilePath($content['uri'], '');
        }

        return $contents;
    }

    public function contentShowAction($contentId)
    {
        $content = $this->getPracticeResultService()->getContent($contentId);
        $content['thumb'] = $this->getWebExtension()->getFilePath($content['uri'], '');
        $posts = $this->getPracticeResultService()->findPostsByContentId($contentId);

        $userIds = ArrayToolkit::column($posts, 'userId');
        $userIds[] = $content['userId'];
        $parentIds =  ArrayToolkit::column($posts, 'parentId');
        $userIds = array_merge($userIds, $parentIds);
        $users = $this->getUserService()->findUsersByIds($userIds);

        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);
        $contents = array();

        foreach ($posts as $post) {
            $result = array(
                'avatar' => $this->userAvatar($users[$post['userId']]['smallAvatar'], 'avatar.png'),
                'userId' => $post['userId'],
                'postId' => $post['id'],
                'name' => empty($profiles[$post['userId']]['truename']) ? $users[$post['userId']]['nickname'] : $profiles[$post['userId']]['truename'],
                'content' => $post['content'],
                'replyName' => null,
                'date' => $post['createdTime'],
            );
            if (!empty($post['parentId'])) {
                $result['replyName'] = empty($profiles[$post['parentId']]['truename']) ? $users[$post['parentId']]['nickname'] : $profiles[$post['parentId']]['truename'];
            }
            $contents[] = $result;
        }

        $content = array(
            'avatar' => $this->userAvatar($users[$content['userId']]['smallAvatar']),
            'name' => empty($profiles[$content['userId']]['truename']) ? $users[$content['userId']]['nickname'] : $profiles[$content['userId']]['truename'],
            'uri' => $content['uri'],
            'thumb' => $this->getWebExtension()->getFilePath($content['uri'], ''),
        );

        return $this->createJsonResponse(array(
            'content' => $content,
            'posts' => $contents,
        ));
    }

    public function postContentAction(Request $request, $contentId)
    {
        $fields = $request->request->all();
        $fields['contentId'] = $contentId;

        $post = $this->getPracticeResultService()->createPost($fields);
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

    public function remarkAction(Request $request, $resultId)
    {
        $fields = $request->request->all();
        $fields['remark'] = explode(',', $fields['remark']);

        $result = $this->getPracticeResultService()->remark($resultId, $fields);

        return $this->createJsonResponse($result['score']);
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

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }
 
    protected function getPracticeResultService()
    {
        return $this->createService('CustomBundle:Practice:PracticeResultService');
    }

    private function userAvatar($avatar)
    {
        return $this->getWebExtension()->getFpath($avatar, 'avatar.png');
    }
}