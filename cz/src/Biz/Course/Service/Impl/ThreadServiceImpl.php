<?php

namespace Biz\Course\Service\Impl;

use Biz\BaseService;
use Biz\Course\Dao\ThreadDao;
use Biz\User\Service\UserService;
use AppBundle\Common\ArrayToolkit;
use Biz\System\Service\LogService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\Course\Service\ThreadService;
use Codeages\Biz\Framework\Event\Event;
use Biz\User\Service\NotificationService;
use Biz\Course\Dao\Impl\ThreadPostDaoImpl;
use Biz\Sensitive\Service\SensitiveService;

class ThreadServiceImpl extends BaseService implements ThreadService
{
    public function countThreads($conditions)
    {
        $conditions = $this->prepareThreadSearchConditions($conditions);

        return $this->getThreadDao()->count($conditions);
    }

    public function searchThreads($conditions, $sort, $start, $limit)
    {
        $orderBys = $this->filterSort($sort);
        $conditions = $this->prepareThreadSearchConditions($conditions);

        return $this->getThreadDao()->search($conditions, $orderBys, $start, $limit);
    }

    public function getThread($courseId, $threadId)
    {
        $thread = $this->getThreadDao()->get($threadId);
        if (!empty($thread) && !empty($courseId) && $thread['courseId'] != $courseId) {
            throw $this->createNotFoundException("Thread#{$threadId} Not Found in Course#{$courseId}");
        }

        return $thread;
    }

    public function findThreadsByType($courseId, $type, $sort, $start, $limit)
    {
        if ($sort === 'latestPosted') {
            $orderBy = array('latestPosted' => 'DESC');
        } else {
            $orderBy = array('createdTime' => 'DESC');
        }

        if (!in_array($type, array('question', 'discussion'))) {
            $type = 'all';
        }

        if ($type === 'all') {
            return $this->getThreadDao()->search(array('courseId' => $courseId), $orderBy, $start, $limit);
        }

        return $this->getThreadDao()->search(array('courseId' => $courseId, 'type' => $type), $orderBy, $start, $limit);
    }

    public function findLatestThreadsByType($type, $start, $limit)
    {
        return $this->getThreadDao()->search(array('type' => $type), array('createdTime' => 'DESC'), $start, $limit);
    }

    public function findEliteThreadsByType($type, $status, $start, $limit)
    {
        return $this->getThreadDao()->search(array('type' => $type, 'isElite' => $status), array('createdTime' => 'DESC'), $start, $limit);
    }

    public function searchThreadCountInCourseIds($conditions)
    {
        $conditions = $this->prepareThreadSearchConditions($conditions);

        return $this->getThreadDao()->count($conditions);
    }

    public function searchThreadInCourseIds($conditions, $sort, $start, $limit)
    {
        $orderBys = $this->filterSort($sort);
        $conditions = $this->prepareThreadSearchConditions($conditions);

        return $this->getThreadDao()->search($conditions, $orderBys, $start, $limit);
    }

    public function searchThreadPosts($conditions, $sort, $start, $limit)
    {
        if (is_array($sort)) {
            $orderBy = $sort;
        } elseif ($sort === 'createdTimeByAsc') {
            $orderBy = array('createdTime' => 'ASC');
        } else {
            $orderBy = array('createdTime' => 'DESC');
        }

        return $this->getThreadPostDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function searchThreadPostsCount($conditions)
    {
        return $this->getThreadPostDao()->count($conditions);
    }

    public function createThread($thread)
    {
        if (empty($thread['courseId'])) {
            throw $this->createServiceException('Course ID can not be empty.');
        }

        if (empty($thread['type']) || !in_array($thread['type'], array('discussion', 'question'))) {
            throw $this->createServiceException(sprintf('Thread type(%s) is error.', $thread['type']));
        }

        $event = $this->dispatchEvent('course.thread.before_create', $thread);

        if ($event->isPropagationStopped()) {
            throw $this->createServiceException('发帖次数过多，请稍候尝试。');
        }

        $thread['content'] = $this->sensitiveFilter($thread['content'], 'course-thread-create');
        $thread['title'] = $this->sensitiveFilter($thread['title'], 'course-thread-create');

        list($course, $member) = $this->getCourseService()->tryTakeCourse($thread['courseId']);

        $thread['userId'] = $this->getCurrentUser()->id;
        $thread['title'] = $this->biz['html_helper']->purify(empty($thread['title']) ? '' : $thread['title']);
        $thread['courseSetId'] = $course['courseSetId'];

        //if user can manage course, we trusted rich editor content
        $hasCourseManagerRole = $this->getCourseService()->hasCourseManagerRole($thread['courseId']);
        $trusted = empty($hasCourseManagerRole) ? false : true;
        //更新thread过滤html
        $thread['content'] = $this->biz['html_helper']->purify($thread['content'], $trusted);

        $thread['createdTime'] = time();
        $thread['latestPostUserId'] = $thread['userId'];
        $thread['latestPostTime'] = $thread['createdTime'];
        $thread['private'] = $course['status'] === 'published' ? 0 : 1;

        $thread = $this->getThreadDao()->create($thread);

        foreach ($course['teacherIds'] as $teacherId) {
            if ($teacherId == $thread['userId']) {
                continue;
            }

            if ($thread['type'] !== 'question') {
                continue;
            }

            $this->getNotifiactionService()->notify($teacherId, 'thread', array(
                'threadId' => $thread['id'],
                'threadUserId' => $thread['userId'],
                'threadUserNickname' => $this->getCurrentUser()->nickname,
                'threadTitle' => $thread['title'],
                'threadType' => $thread['type'],
                'courseId' => $course['id'],
                'courseTitle' => $course['title'],
            ));
        }

        $this->dispatchEvent('course.thread.create', new Event($thread));

        return $thread;
    }

    public function updateThread($courseId, $threadId, $fields)
    {
        $thread = $this->getThread($courseId, $threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException("Thread #{$threadId} Not Found");
        }

        $fields['content'] = $this->sensitiveFilter($fields['content'], 'course-thread-update');
        $fields['title'] = $this->sensitiveFilter($fields['title'], 'course-thread-update');

        if ($this->getCurrentUser()->getId() != $thread['userId']) {
            $this->getCourseService()->tryManageCourse($thread['courseId'], 'admin_course_thread');
        }

        $fields = ArrayToolkit::parts($fields, array('title', 'content'));

        if (empty($fields)) {
            throw $this->createInvalidArgumentException('Fields Required');
        }
        //if user can manage course, we trusted rich editor content
        $hasCourseManagerRole = $this->getCourseService()->hasCourseManagerRole($courseId);
        $trusted = empty($hasCourseManagerRole) ? false : true;
        //更新thread过滤html
        $fields['content'] = $this->biz['html_helper']->purify($fields['content'], $trusted);

        $thread = $this->getThreadDao()->update($threadId, $fields);
        $this->dispatchEvent('course.thread.update', new Event($thread));

        return $thread;
    }

    public function deleteThread($threadId)
    {
        $thread = $this->getThreadDao()->get($threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException("Thread #{$threadId} Not Found");
        }
        $this->getCourseService()->tryManageCourse($thread['courseId']);

        $this->getThreadPostDao()->deleteByThreadId($threadId);
        $this->getThreadDao()->delete($threadId);

        $this->dispatchEvent('course.thread.delete', new Event($thread));
        $this->getLogService()->info('course', 'delete_thread', "删除话题 {$thread['title']}({$thread['id']})");
    }

    public function stickThread($courseId, $threadId)
    {
        $this->getCourseService()->tryManageCourse($courseId, 'admin_course_thread');

        $thread = $this->getThread($courseId, $threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException("Thread #{$threadId} Not Found");
        }

        $thread = $this->getThreadDao()->update($thread['id'], array('isStick' => 1));

        $this->dispatchEvent('course.thread.stick', new Event($thread));
    }

    public function unstickThread($courseId, $threadId)
    {
        $this->getCourseService()->tryManageCourse($courseId, 'admin_course_thread');

        $thread = $this->getThread($courseId, $threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException("Thread #{$threadId} Not Found");
        }

        $thread = $this->getThreadDao()->update($thread['id'], array('isStick' => 0));

        $this->dispatchEvent('course.thread.unstick', new Event($thread));
    }

    public function eliteThread($courseId, $threadId)
    {
        $this->getCourseService()->tryManageCourse($courseId, 'admin_course_thread');

        $thread = $this->getThread($courseId, $threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException("Thread #{$threadId} Not Found");
        }

        $thread = $this->getThreadDao()->update($thread['id'], array('isElite' => 1));

        $this->dispatchEvent('course.thread.elite', new Event($thread));
    }

    public function uneliteThread($courseId, $threadId)
    {
        $this->getCourseService()->tryManageCourse($courseId, 'admin_course_thread');

        $thread = $this->getThread($courseId, $threadId);

        if (empty($thread)) {
            throw $this->createNotFoundException("Thread #{$threadId} Not Found");
        }

        $thread = $this->getThreadDao()->update($thread['id'], array('isElite' => 0));

        $this->dispatchEvent('course.thread.unelite', new Event($thread));
    }

    public function hitThread($courseId, $threadId)
    {
        $thread = $this->getThread($courseId, $threadId);
        if (empty($thread)) {
            return;
        }

        $this->getThreadDao()->wave(array($threadId), array('hitNum' => +1));
    }

    public function findThreadPosts($courseId, $threadId, $sort, $start, $limit)
    {
        $thread = $this->getThread($courseId, $threadId);

        if (empty($thread)) {
            return array();
        }

        if ($sort === 'best') {
            $orderBy = array('score' => 'DESC');
        } elseif ($sort === 'elite') {
            $orderBy = array('createdTime' => 'DESC', 'isElite' => 'ASC');
        } else {
            $orderBy = array('createdTime' => 'ASC');
        }

        return $this->getThreadPostDao()->search(array('threadId' => $threadId), $orderBy, $start, $limit);
    }

    public function getThreadPostCount($courseId, $threadId)
    {
        return $this->getThreadPostDao()->count(array('threadId' => $threadId));
    }

    public function findThreadElitePosts($courseId, $threadId, $start, $limit)
    {
        return $this->getThreadPostDao()->search(array('threadId' => $threadId, 'isElite' => 1), array('createdTime' => 'ASC'), $start, $limit);
    }

    public function getPostCountByuserIdAndThreadId($userId, $threadId)
    {
        return $this->getThreadPostDao()->count(array('userId' => $userId, 'threadId' => $threadId));
    }

    public function getThreadPostCountByThreadId($threadId)
    {
        return $this->getThreadPostDao()->count(array('threadId' => $threadId));
    }

    public function getMyReplyThreadCount()
    {
        $conditions = array(
            'userId' => $this->getCurrentUser()->getId(),
        );

        return $this->getThreadPostDao()->countGroupByThreadId($conditions);
    }

    public function getMyLatestReplyPerThread($start, $limit)
    {
        return $this->getThreadPostDao()->searchByUserIdGroupByThreadId($this->getCurrentUser()->getId(), $start, $limit);
    }

    public function getPost($courseId, $id)
    {
        return $this->getThreadPostDao()->get($id);
    }

    public function postAtNotifyEvent($post, $users)
    {
        $this->dispatchEvent('course.thread.post.at', $post, array('users' => $users));
    }

    public function createPost($post)
    {
        $requiredKeys = array('courseId', 'threadId', 'content');

        if (!ArrayToolkit::requireds($post, $requiredKeys)) {
            throw $this->createInvalidArgumentException('Fields Required');
        }

        $event = $this->dispatchEvent('course.thread.post.before_create', $post);

        if ($event->isPropagationStopped()) {
            throw $this->createAccessDeniedException('Creating too frequently');
        }

        $thread = $this->getThread($post['courseId'], $post['threadId']);

        if (empty($thread)) {
            throw $this->createNotFoundException("Thread#{$post['threadId']} Not Found");
        }

        $post['content'] = $this->sensitiveFilter($post['content'], 'course-thread-post-create');

        $this->getCourseService()->tryTakeCourse($post['courseId']);

        $post['userId'] = $this->getCurrentUser()->id;
        $post['isElite'] = $this->getMemberService()->isCourseTeacher($post['courseId'], $post['userId']) ? 1 : 0;
        $post['createdTime'] = time();

        //if user can manage course, we trusted rich editor content
        $hasCourseManagerRole = $this->getCourseService()->hasCourseManagerRole($post['courseId']);
        $trusted = empty($hasCourseManagerRole) ? false : true;
        //创建post过滤html
        $post['content'] = $this->biz['html_helper']->purify($post['content'], $trusted);

        $post = $this->getThreadPostDao()->create($post);

        // 高并发的时候， 这样更新postNum是有问题的，这里暂时不考虑这个问题。
        $threadFields = array(
            'postNum' => $thread['postNum'] + 1,
            'latestPostUserId' => $post['userId'],
            'latestPostTime' => $post['createdTime'],
        );
        $this->getThreadDao()->update($thread['id'], $threadFields);

        $this->dispatchEvent('course.thread.post.create', $post);

        return $post;
    }

    public function updatePost($courseId, $id, $fields)
    {
        $fields['content'] = $this->sensitiveFilter($fields['content'], 'course-thread-post-update');

        $post = $this->getPost($courseId, $id);

        if (empty($post)) {
            throw $this->createNotFoundException("Post #{$id} Not Found");
        }

        $user = $this->getCurrentUser();
        ($user->isLogin() && $user->id == $post['userId']) || $this->getCourseService()->tryManageCourse($courseId, 'admin_course_thread');

        $fields = ArrayToolkit::parts($fields, array('content'));

        if (empty($fields)) {
            throw $this->createInvalidArgumentException('Fields Required');
        }

        //if user can manage course, we trusted rich editor content
        $hasCourseManagerRole = $this->getCourseService()->hasCourseManagerRole($courseId);
        $trusted = empty($hasCourseManagerRole) ? false : true;
        //更新post过滤html
        $fields['content'] = $this->biz['html_helper']->purify($fields['content'], $trusted);

        $post = $this->getThreadPostDao()->update($id, $fields);
        $this->dispatchEvent('course.thread.post.update', $post);

        return $post;
    }

    public function deletePost($courseId, $id)
    {
        $this->getCourseService()->tryManageCourse($courseId, 'admin_course_thread');

        $post = $this->getThreadPostDao()->get($id);

        if (empty($post)) {
            throw $this->createNotFoundException("Post #{$id} Not Found");
        }

        if ($post['courseId'] != $courseId) {
            throw $this->createAccessDeniedException("No Such Post#{$id} in Course#{$courseId}");
        }

        $this->getThreadPostDao()->delete($post['id']);
        $this->getThreadDao()->wave(array($post['threadId']), array('postNum' => -1));
        $this->dispatchEvent('course.thread.post.delete', $post);
    }

    protected function prepareThreadSearchConditions($conditions)
    {
        if (isset($conditions['threadType'])) {
            $conditions[$conditions['threadType']] = 1;
        }

        if (isset($conditions['keywordType'], $conditions['keyword'])) {
            if (!in_array($conditions['keywordType'], array('title', 'content', 'courseId', 'courseTitle'))) {
                throw $this->createInvalidArgumentException('Invalid keywordType');
            }

            $conditions[$conditions['keywordType']] = $conditions['keyword'];
            unset($conditions['keywordType']);
            unset($conditions['keyword']);
        }

        if (!empty($conditions['author'])) {
            $author = $this->getUserService()->getUserByNickname($conditions['author']);
            $conditions['userId'] = $author ? $author['id'] : -1;
        }

        return $conditions;
    }

    protected function filterSort($sort)
    {
        if (is_array($sort)) {
            return $sort;
        }

        switch ($sort) {
            case 'created':
                $orderBys = array('isStick' => 'DESC', 'createdTime' => 'DESC');
                break;
            case 'posted':
                $orderBys = array('isStick' => 'DESC', 'latestPostTime' => 'DESC');
                break;
            case 'createdNotStick':
                $orderBys = array('createdTime' => 'DESC');
                break;
            case 'postedNotStick':
                $orderBys = array('latestPostTime' => 'DESC');
                break;
            case 'popular':
                $orderBys = array('hitNum' => 'DESC');
                break;
            default:
                throw $this->createInvalidArgumentException('Invalid sort');
        }

        return $orderBys;
    }

    protected function sensitiveFilter($str, $type)
    {
        return $this->getSensitiveService()->sensitiveCheck($str, $type);
    }

    /**
     * @return ThreadDao
     */
    protected function getThreadDao()
    {
        return $this->createDao('Course:ThreadDao');
    }

    /**
     * @return ThreadPostDaoImpl
     */
    protected function getThreadPostDao()
    {
        return $this->createDao('Course:ThreadPostDao');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return SensitiveService
     */
    protected function getSensitiveService()
    {
        return $this->createService('Sensitive:SensitiveService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return NotificationService
     */
    protected function getNotifiactionService()
    {
        return $this->createService('User:NotificationService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return MemberService
     */
    protected function getMemberService()
    {
        return $this->createService('Course:MemberService');
    }
}
