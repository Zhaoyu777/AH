<?php

namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Group\Service\ThreadService;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Controller\Weixin\WeixinBaseController;

class ThreadController extends WeixinBaseController
{
    public function createThreadAction(Request $request, $groupId)
    {
        $user = $this->getCurrentUser();
        $data = $request->request->all();

        $data['groupId'] = $groupId;
        $data['userId'] = $user['id'];

        $thread = $this->getThreadService()->addThread($data);

        return $this->createJsonResponse($thread);
    }

    public function threadsAction(Request $request, $groupId)
    {
        $user = $this->getCurrentUser();
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);

        $conditions = array();
        if ($groupId != 0) {
            $conditions['groupId'] = $groupId;
        } elseif ($groupId == 0 && $page > 4) {
            return $this->createJsonResponse();
        }

        $total = $this->getThreadService()->countThreads($conditions);
        $threads = $this->getThreadService()->searchThreads(
            $conditions,
            array('createdTime' => 'DESC'),
            ($page - 1) * $limit,
            $limit
        );
        $userIds = ArrayToolkit::column($threads, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $groupIds = ArrayToolkit::column($threads, 'groupId');
        $groups = $this->getGroupService()->getGroupsByIds($groupIds);

        $result = array();
        foreach ($threads as $key => $thread) {
            $result[] = array(
                'id' => $thread['id'],
                'title' => $thread['title'],
                'nickname' => $users[$thread['userId']]['nickname'],
                'avatar' => $this->getWebExtension()->getFpath($users[$thread['userId']]['smallAvatar'], 'avatar.png'),
                'timeStr' => $this->getWebExtension()->smarttimeFilter($thread['createdTime']),
                'groupTitle' => $groups[$thread['groupId']]['title'],
                'content' => $thread['content'],
                'hitNum' => $thread['hitNum'],
                'postNum' => $thread['postNum'],
                'groupId' => $thread['groupId'],
            );
        }

        $paging = array(
            'total' => ceil($total/$limit),
            'page' => $page,
            'limit' => $limit
        );

        return $this->createJsonResponse(array(
            'paging'=> $paging,
            'data' => $result
        ));
    }

    public function postThreadAction(Request $request, $groupId, $threadId)
    {
        $user = $this->getCurrentUser();

        $content = array(
            'content' => $request->request->get('content'),
            'fromUserId' => 0,
            'postId' => $request->request->get('postId', 0),
        );

        $content = $this->postContent($content);

        $post = $this->getThreadService()->postThread($content, $groupId, $user['id'], $threadId, $content['postId']);
        $childPosts = $this->childPosts($post['postId']);

        return $this->createJsonResponse(array(
            'childPosts' => $childPosts,
            'postId' => $content['postId']
        ));
    }

    protected function postContent($content)
    {
        $postId = $content['postId'];

        if ($postId != 0) {
            $post = $this->getThreadService()->getPost($postId);

            if ($post['postId'] != 0) {
                $user = $this->getUserService()->getUser($post['userId']);
                $content['content'] = "回复{$user['truename']}:{$content['content']}";
                $content['postId'] = $post['postId'];
            }

            $content['fromUserId'] = $post['userId'];
        }

        return $content;
    }

    public function postsAction(Request $request, $groupId, $threadId)
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $conditions = array(
            'threadId' => $threadId,
            'postId' => 0,
        );

        $total = $this->getThreadService()->searchPostsCount($conditions);
        $thread = $this->getThreadService()->getThread($threadId);
        $posts = $this->getThreadService()->searchPosts(
            $conditions,
            array(),
            ($page - 1) * $limit,
            $limit
        );
        $userIds = ArrayToolkit::column($posts, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $result = array();
        foreach ($posts as $key => $post) {
            $result[] = array(
                'id' => $post['id'],
                'content' => $post['content'],
                'truename' => $users[$post['userId']]['truename'],
                'nickname' => $users[$post['userId']]['nickname'],
                'timeStr' => $this->getWebExtension()->smarttimeFilter($post['createdTime']),
                'avatar' => $this->getWebExtension()->getFpath($users[$post['userId']]['smallAvatar'], 'avatar.png'),
                'childPosts' => $this->childPosts($post['id']),
            );
        }

        $paging = array(
            'total' => ceil($total/$limit),
            'page' => $page,
            'limit' => $limit
        );

        return $this->createJsonResponse(array(
            'paging'=> $paging,
            'data' => $result
        ));
    }

    protected function childPosts($postId)
    {
        $posts = $this->getThreadService()->searchPosts(
            array('postId' => $postId,),
            array(),
            0,
            PHP_INT_MAX
        );
        $userIds = ArrayToolkit::column($posts, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $result = array();
        foreach ($posts as $key => $post) {
            $result[] = array(
                'id' => $post['id'],
                'content' => $post['content'],
                'truename' => $users[$post['userId']]['truename'],
                'nickname' => $users[$post['userId']]['nickname'],
                'timeStr' => $this->getWebExtension()->smarttimeFilter($post['createdTime']),
                'avatar' => $this->getWebExtension()->getFpath($users[$post['userId']]['smallAvatar'], 'avatar.png'),
            );
        }

        return $result;
    }

    public function threadDetailAction(Request $request, $threadId)
    {
        $thread = $this->getThreadService()->getThread($threadId);
        $this->getThreadService()->waveHitNum($thread['id']);

        return $this->createJsonResponse(array(
            'title' => $thread['title'],
            'content' => $thread['content'],
            'hitNum' => $thread['hitNum'],
            'postNum' => $thread['postNum'],
            'timeStr' => $this->getWebExtension()->smarttimeFilter($thread['createdTime']),
        ));
    }

    protected function getThreadService()
    {
        return $this->createService('Group:ThreadService');
    }

    protected function getGroupService()
    {
        return $this->createService('Group:GroupService');
    }
}
