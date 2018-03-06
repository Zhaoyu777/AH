<?php

namespace Biz\Group\Service\Impl;

use Biz\BaseService;
use Biz\Thread\Dao\ThreadDao;
use AppBundle\Common\ArrayToolkit;
use Biz\Group\Dao\ThreadCollectDao;
use Biz\Group\Service\ThreadService;
use Codeages\Biz\Framework\Event\Event;
use Topxia\Service\Common\ServiceKernel;

class ThreadServiceImpl extends BaseService implements ThreadService
{
    public function getThread($id)
    {
        return $this->getThreadDao()->get($id);
    }

    public function searchThreads($conditions, $orderBy, $start, $limit)
    {
        $orderBys = is_array($orderBy) ? $orderBy : $this->filterSort($orderBy);

        return $this->getThreadDao()->search($conditions, $orderBys, $start, $limit);
    }

    public function countThreads($conditions)
    {
        return $this->getThreadDao()->count($conditions);
    }

    public function searchThreadCollects($conditions, $orderBy, $start, $limit)
    {
        return $this->getThreadCollectDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function countThreadCollects($conditions)
    {
        return $this->getThreadCollectDao()->count($conditions);
    }

    public function searchPostsThreadIds($conditions, $orderBy, $start, $limit)
    {
        return $this->getThreadPostDao()->searchPostsThreadIds($conditions, $orderBy, $start, $limit);
    }

    public function countPostsThreadIds($conditions)
    {
        return $this->getThreadPostDao()->countPostsThreadIds($conditions);
    }

    public function isCollected($userId, $threadId)
    {
        $thread = $this->getThreadCollectDao()->getByUserIdAndThreadId($userId, $threadId);

        if (empty($thread)) {
            return false;
        } else {
            return true;
        }
    }

    public function getThreadsByIds($ids)
    {
        $threads = $this->getThreadDao()->findByIds($ids);

        return ArrayToolkit::index($threads, 'id');
    }

    public function threadCollect($userId, $threadId)
    {
        $thread = $this->getThread($threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException('Thread Not Found');
        }

        if ($userId == $thread['userId']) {
            throw $this->createAccessDeniedException('You can not collect your own thread');
        }

        $collectThread = $this->getThreadCollectDao()->getByUserIdAndThreadId($userId, $threadId);

        if (!empty($collectThread)) {
            throw $this->createServiceException('Thread has bean collected');
        }

        $this->dispatchEvent('group.thread.collect', new Event($thread));

        return $this->getThreadCollectDao()->create(array(
            'userId' => $userId,
            'threadId' => $threadId,
        ));
    }

    public function searchGoods($conditions, $orderBy, $start, $limit)
    {
        return $this->getThreadGoodsDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function unThreadCollect($userId, $threadId)
    {
        $thread = $this->getThread($threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException('Thread Not Found');
        }

        $collectThread = $this->getThreadCollectDao()->getByUserIdAndThreadId($userId, $threadId);

        if (empty($collectThread)) {
            throw $this->createNotFoundException("Thread#{$threadId} is not in your collection");
        }

        return $this->getThreadCollectDao()->deleteByUserIdAndThreadId($userId, $threadId);
    }

    public function searchThreadsCount($conditions)
    {
        return $this->getThreadDao()->count($conditions);
    }

    public function getTradeByUserIdAndGoodsId($userId, $goodsId)
    {
        return $this->getThreadTradeDao()->getByUserIdAndGoodsId($userId, $goodsId);
    }

    public function getPost($id)
    {
        return $this->getThreadPostDao()->get($id);
    }

    protected function sensitiveFilter($str, $type)
    {
        return $this->getSensitiveService()->sensitiveCheck($str, $type);
    }

    public function addThread($thread)
    {
        if (empty($thread['title'])) {
            throw $this->createInvalidArgumentException('请输入标题');
        }

        if (empty($thread['content'])) {
            throw $this->createInvalidArgumentException('请输入话题内容');
        }

        $event = $this->dispatchEvent('group.thread.before_create', $thread);

        if ($event->isPropagationStopped()) {
            throw $this->createServiceException('Creating threads too frequently');
        }

        $thread['title'] = $this->sensitiveFilter($thread['title'], 'group-thread-create');
        $thread['content'] = $this->sensitiveFilter($thread['content'], 'group-thread-create');
        $thread['title'] = $this->purifyHtml($thread['title']);
        $thread['content'] = $this->purifyHtml($thread['content']);

        if (empty($thread['groupId'])) {
            throw $this->createInvalidArgumentException('GroupId Required');
        }

        if (empty($thread['userId'])) {
            throw $this->createInvalidArgumentException('UserId Required');
        }

        $thread['createdTime'] = time();
        $thread = $this->getThreadDao()->create($thread);

        $this->getGroupService()->waveGroup($thread['groupId'], 'threadNum', +1);

        $this->getGroupService()->waveMember($thread['groupId'], $thread['userId'], 'threadNum', +1);

        $this->hideThings($thread['content'], $thread['id']);
        $this->dispatchEvent('group.thread.create', $thread);
        $this->getLogService()->info('group', 'create_thread', "新增话题 {$thread['title']}({$thread['id']})");

        return $thread;
    }

    public function deleteGoods($id)
    {
        $this->getThreadGoodsDao()->delete($id);

        return true;
    }

    public function addAttach($files, $threadId)
    {
        $user = $this->getCurrentUser();

        for ($i = 0; $i < count($files['id']); ++$i) {
            $file = $this->getFileService()->getFile($files['id'][$i]);

            if ($file['userId'] != $user->id) {
                continue;
            }

            $hide = $this->getThreadGoodsDao()->search(array('threadId' => $threadId, 'fileId' => $files['id'][$i]), array('createdTime' => 'desc'), 0, 1);

            $files['title'][$i] = $this->subTxt($files['title'][$i]);

            $attach = array(
                'title' => $files['title'][$i],
                'description' => $files['description'][$i],
                'type' => 'attachment',
                'userId' => $user->id,
                'threadId' => $threadId,
                'coin' => $files['coin'][$i],
                'fileId' => $files['id'][$i],
                'createdTime' => time(),
            );

            if ($hide) {
                $this->getThreadGoodsDao()->update($hide[0]['id'], $attach);
                continue;
            }

            $this->getThreadGoodsDao()->create($attach);
        }
    }

    public function addPostAttach($files, $threadId, $postId)
    {
        $user = $this->getCurrentUser();

        for ($i = 0; $i < count($files['id']); ++$i) {
            $file = $this->getFileService()->getFile($files['id'][$i]);

            if ($file['userId'] != $user->id) {
                continue;
            }

            $files['title'][$i] = $this->subTxt($files['title'][$i]);

            $attach = array(
                'title' => $files['title'][$i],
                'description' => $files['description'][$i],
                'type' => 'postAttachment',
                'userId' => $user->id,
                'threadId' => $threadId,
                'coin' => $files['coin'][$i],
                'fileId' => $files['id'][$i],
                'postId' => $postId,
                'createdTime' => time(),
            );

            $this->getThreadGoodsDao()->create($attach);
        }
    }

    public function waveGoodsHitNum($goodsId)
    {
        return $this->getThreadGoodsDao()->wave(array($goodsId), array('hitnum', 1));
    }

    protected function hideThings($content, $id)
    {
        $content = str_replace('#', '<!--></>', $content);
        $content = str_replace('[hide=coin', '#[hide=coin', $content);

        $user = $this->getCurrentUser();
        $data = explode('[/hide]', $content);

        foreach ($data as $key => $value) {
            $value = ' '.$value;
            sscanf($value, '%[^#]#[hide=coin%[^]]]%[^$$]', $content, $coin, $title);

            if (!is_numeric($coin)) {
                $coin = 0;
            }

            if ($coin >= 0 && $title != '') {
                $hide = array(
                    'title' => $title,
                    'type' => 'content',
                    'threadId' => $id,
                    'coin' => $coin,
                    'userId' => $user->id,
                );
                $this->getThreadGoodsDao()->create($hide);
            }

            unset($coin);
            unset($title);
        }
    }

    protected function subTxt($string, $length = 10)
    {
        $string = explode('.', $string);

        $text = $this->pureString($string);

        $length = (int) $length;

        if (($length > 0) && (mb_strlen($text, 'utf-8') > $length)) {
            $text = mb_substr($text, 0, $length, 'UTF-8');
        }

        return $text.'.'.$string[count($string) - 1];
    }

    protected function pureString($string)
    {
        $text = $string[0];
        $text = strip_tags($text);

        $text = str_replace(array("\n", "\r", "\t"), '', $text);
        $text = str_replace('&nbsp;', ' ', $text);

        return trim($text);
    }

    public function getGoods($id)
    {
        return $this->getThreadGoodsDao()->get($id);
    }

    public function sumGoodsCoinsByThreadId($id)
    {
        $condition = array('threadId' => $id, 'type' => 'content');

        return $this->getThreadGoodsDao()->sumGoodsCoins($condition);
    }

    public function waveHitNum($threadId)
    {
        $this->getThreadDao()->wave(array($threadId), array('hitNum' => 1));
    }

    public function addTrade($fields)
    {
        if (empty($fields['userId'])) {
            throw $this->createInvalidArgumentException('UserId Required');
        }

        return $this->getThreadTradeDao()->addTrade($fields);
    }

    public function updateThread($id, $fields)
    {
        if (empty($fields['title'])) {
            throw $this->createInvalidArgumentException('Title Required');
        }

        if (empty($fields['content'])) {
            throw $this->createInvalidArgumentException('Content Required');
        }

        $fields['title'] = $this->sensitiveFilter($fields['title'], 'group-thread-update');
        $fields['content'] = $this->sensitiveFilter($fields['content'], 'group-thread-update');

        $this->getThreadGoodsDao()->deleteByThreadIdAndType($id, 'content');
        $this->hideThings($fields['content'], $id);

        $fields['title'] = $this->purifyHtml($fields['title']);
        $fields['content'] = $this->purifyHtml($fields['content']);

        $thread = $this->getThreadDao()->update($id, $fields);
        $this->dispatchEvent('group.thread.update', $thread);

        return $thread;
    }

    public function closeThread($threadId)
    {
        $thread = $this->getThreadDao()->update($threadId, array('status' => 'close'));
        $this->dispatchEvent('group.thread.close', $thread);
        $this->getLogService()->info('group', 'close_thread', "关闭话题 {$thread['title']}({$thread['id']})");
    }

    public function openThread($threadId)
    {
        $thread = $this->getThreadDao()->update($threadId, array('status' => 'open'));
        $this->dispatchEvent('group.thread.open', $thread);
        $this->getLogService()->info('group', 'open_thread', "开启话题 {$thread['title']}({$thread['id']})");
    }

    public function postThread($threadContent, $groupId, $memberId, $threadId, $postId = 0)
    {
        if (empty($threadContent['content'])) {
            throw $this->createInvalidArgumentException('请输入回复内容');
        }

        $event = $this->dispatchEvent('group.thread.post.before_create', $threadContent);

        if ($event->isPropagationStopped()) {
            throw $this->createServiceException('Creating threads too frequently');
        }

        $threadContent['content'] = $this->sensitiveFilter($threadContent['content'], 'group-thread-post-create');
        $threadContent['content'] = $this->purifyHtml($threadContent['content']);
        $threadContent['userId'] = $memberId;
        $threadContent['createdTime'] = time();
        $threadContent['threadId'] = $threadId;
        $threadContent['postId'] = $postId;
        $post = $this->getThreadPostDao()->create($threadContent);
        $this->getThreadDao()->update($threadId, array('lastPostMemberId' => $memberId, 'lastPostTime' => time()));
        $this->getGroupService()->waveGroup($groupId, 'postNum', +1);
        $this->getGroupService()->waveMember($groupId, $memberId, 'postNum', +1);

        if ($postId == 0) {
            $this->waveThread($threadId, 'postNum', +1);
        }

        $thread = $this->getThread($threadId);

        $this->dispatchEvent('group.thread.post.create', $post);

        return $post;
    }

    public function searchPosts($conditions, $orderBy, $start, $limit)
    {
        return $this->getThreadPostDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function searchPostsCount($conditions)
    {
        return $this->getThreadPostDao()->count($conditions);
    }

    public function setElite($threadId)
    {
        $this->getThreadDao()->update($threadId, array('isElite' => 1));
    }

    public function removeElite($threadId)
    {
        $this->getThreadDao()->update($threadId, array('isElite' => 0));
    }

    public function setStick($threadId)
    {
        $this->getThreadDao()->update($threadId, array('isStick' => 1));
    }

    public function removeStick($threadId)
    {
        $this->getThreadDao()->update($threadId, array('isStick' => 0));
    }

    public function deleteThread($threadId)
    {
        $thread = $this->getThreadDao()->get($threadId);
        $this->deletePostsByThreadId($threadId);
        $this->getThreadDao()->delete($threadId);

        $this->getGroupService()->waveGroup($thread['groupId'], 'threadNum', -1);

        $this->getGroupService()->waveMember($thread['groupId'], $thread['userId'], 'threadNum', -1);
        $this->dispatchEvent('group.thread.delete', $thread);
        $this->getLogService()->info('group', 'delete_thread', "删除话题 {$thread['title']}({$thread['id']})");
    }

    public function updatePost($id, $fields)
    {
        if (!empty($fields['content'])) {
            $fields['content'] = $this->sensitiveFilter($fields['content'], 'group-thread-post-update');
            $fields['content'] = $this > purifyHtml($fields['content']);
        }

        $post = $this->getThreadPostDao()->update($id, $fields);
        $this->dispatchEvent('group.thread.post.update', $post);

        return $post;
    }

    public function deletePost($postId)
    {
        $post = $this->getThreadPostDao()->get($postId);
        $threadId = $post['threadId'];
        $thread = $this->getThreadDao()->get($threadId);

        $this->getThreadPostDao()->delete($postId);

        $this->getGroupService()->waveGroup($thread['groupId'], 'postNum', -1);

        $this->getGroupService()->waveMember($thread['groupId'], $thread['userId'], 'postNum', -1);

        $this->waveThread($threadId, 'postNum', -1);

        $this->dispatchEvent('group.thread.post.delete', $post);
    }

    public function deletePostsByThreadId($threadId)
    {
        $thread = $this->getThreadDao()->get($threadId);
        $postCount = $this->getThreadPostDao()->count(array('threadId' => $threadId));

        $this->getGroupService()->waveGroup($thread['groupId'], 'postNum', -$postCount);

        $this->getGroupService()->waveMember($thread['groupId'], $thread['userId'], 'postNum', -$postCount);

        $this->getThreadPostDao()->deleteByThreadId($threadId);
    }

    public function getTrade($id)
    {
        return $this->getThreadTradeDao()->get($id);
    }

    protected function waveThread($id, $field, $diff)
    {
        return $this->getThreadDao()->wave(array($id), array($field => $diff));
    }

    public function getTradeByUserIdAndThreadId($userId, $threadId)
    {
        return $this->getThreadTradeDao()->getByUserIdAndThreadId($userId, $threadId);
    }

    protected function filterSort($sort)
    {
        switch ($sort) {
            case 'byPostNum':
                $orderBys = array('isStick' => 'DESC', 'postNum' => 'DESC', 'createdTime' => 'DESC');
                break;
            case 'byStick':
            case 'byCreatedTime':
                $orderBys = array('isStick' => 'DESC', 'createdTime' => 'DESC');
                break;
            case 'byLastPostTime':
                $orderBys = array('isStick' => 'DESC', 'lastPostTime' => 'DESC');
                break;
            case 'byCreatedTimeOnly':
                $orderBys = array('createdTime' => 'DESC');
                break;
            default:
                throw $this->createInvalidArgumentException('Invalid Orderby');
        }

        return $orderBys;
    }

    protected function getSensitiveService()
    {
        return ServiceKernel::instance()->createService('Sensitive:SensitiveService');
    }

    protected function getGroupService()
    {
        return $this->biz->service('Group:GroupService');
    }

    protected function getLogService()
    {
        return $this->biz->service('System:LogService');
    }

    /**
     * @return ThreadDao
     */
    protected function getThreadDao()
    {
        return $this->createDao('Group:ThreadDao');
    }

    /**
     * @return ThreadCollectDao
     */
    protected function getThreadCollectDao()
    {
        return $this->createDao('Group:ThreadCollectDao');
    }

    protected function getThreadPostDao()
    {
        return $this->createDao('Group:ThreadPostDao');
    }

    protected function getThreadGoodsDao()
    {
        return $this->createDao('Group:ThreadGoodsDao');
    }

    protected function getThreadTradeDao()
    {
        return $this->createDao('Group:ThreadTradeDao');
    }
}
