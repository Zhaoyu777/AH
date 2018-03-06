<?php

namespace CustomBundle\Biz\Course\Event;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;

class SocketPushSubscriber extends SocketEventSubscriber
{
    public static function getSubscribedEvents()
    {
        return array(
            'course.lesson.start' => 'onLessonStart',
            'push.lesson.end' => 'onLessonEnd',
            'course.lesson.cancel' => 'onLessonCancel',
            'push.signIn.create' => 'onSignInStart',
            'signIn.cancel' => 'onSignInCancel',
            'signIn.student.attend' => 'onStudentAttend',
            'signIn.end' => 'onSignInEnd',
            'attendance.set' => 'onAttendanceSet',
            'display.wall.content.like' => 'onDisplayWallContentLike',
            'display.wall.content.cancelLike' => 'onDisplayWallContentCancelLike',
            'change_display_wall_image' => 'onChangeDisplayWallImage',
            'display.wall.content.create' => 'onDisplayWallContentCreate',
            'one_sentence.result.create' => 'onOneSentenceResultCreate',
            'race.answer.create' => 'onRaceAnswerCreate',
            'push.task.start' => 'onTaskStart',
            'push.task.end' => 'onTaskEnd',
            'push.rollcall.result.create' => 'onRollcallResultCreate',
            'questionnaire.finished' => 'onQuestionnaireFinished',
            'brain_storm.create' => 'onBrainStormChange',
            'brain_storm.update' => 'onBrainStormChange',
            'task.result.remark' => 'onTaskResultRemark',
            'display.wall.post.create' => 'onDisplayWallPostCreate',
            'random.group.join' => 'onRandomGroupJoin',
            'course.task.start' => 'onCourseTaskStart',
            'course.task.finish' => 'courseTaskFinish',
            'random.testpaper.create' => 'onRandomTestpaperCreate',
            'practice.work.create' => 'onPracticeWorkCreate',
            'practice.work.update' => 'onPracticeWorkUpdate',
            'lesson.record.create' => 'onLessonRecordChange',
            'lesson.record.update' => 'onLessonRecordChange',
            'practice.content.like' => 'onPracticeContentLike',
            'practice.content.cancelLike' => 'onPracticeContentCancelLike',
            'change.practice.image' => 'onChangePracticeImage',
            'practice.content.create' => 'onPracticeContentCreate',
            'practice.post.create' => 'onPracticePostCreate',
        );
    }

    //Practice socket
    public function onChangePracticeImage(Event $event)
    {
        try {
            $content = $event->getSubject();
            $result = $this->getPracticeResultService()->getResult($content['resultId']);
            $content['thumb'] = $this->getFilePath($content['uri'], '');
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
            $isLike = $this->getPracticeResultService()->isLike($content['id']);
            $result['isStar'] = $isLike ? 1 : 0;

            $this->emit('change practice image', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'content' => $content,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onPracticeContentCreate(Event $event)
    {
        try {
            $content = $event->getSubject();
            $result = $this->getPracticeResultService()->getResult($content['resultId']);
            $result['isStar'] = 0;
            $user = $this->getUserService()->getUser($content['userId']);
            $user['avatar'] = $this->getFilePath($user['smallAvatar'], 'avatar.png');
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);

            $content['thumb'] = $this->getFilePath($content['uri'], '');
            $routings = array(
                'contentShow' =>
                $this->generateUrl(
                    'practice_content_show',
                    array('contentId' => $content['id'])
                ),
                'cancelLike' =>
                $this->generateUrl(
                    'practice_content_cancel_like',
                    array('contentId' => $content['id'])
                ),
                'like' =>
                $this->generateUrl(
                    'practice_content_like',
                    array('contentId' => $content['id'])
                ),
                'remark' =>
                $this->generateUrl(
                    'practice_result_remark',
                    array('resultId' => $result['id'])
                ),
            );

            $this->emit('create practice result', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'user' => $user,
                'result' => $result,
                'content' => $content,
                'routings' => $routings,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onPracticePostCreate(Event $event)
    {
        try {
            $post = $event->getSubject();
            $user = $this->getUserService()->getUser($post['userId']);

            $content = $this->getPracticeResultService()->getContent($post['contentId']);

            $result = $this->getPracticeResultService()->getResult($content['resultId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);

            $this->emit('practice post num', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'postNum' => $content['postNum'],
                'post' => $post,
                'user' => $user,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onPracticeContentLike(Event $event)
    {
        try {
            $like = $event->getSubject();
            $content = $this->getPracticeResultService()->getContent($like['contentId']);
            $result = $this->getPracticeResultService()->getResult($content['resultId']);
            $result['isStar'] = 1;
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);

            $this->emit('like practice content', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'likeUserId' => $like['userId'],
                'content' => $content,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onPracticeContentCancelLike(Event $event)
    {
        try {
            $like = $event->getSubject();
            $content = $this->getPracticeResultService()->getContent($like['contentId']);
            $result = $this->getPracticeResultService()->getResult($content['resultId']);
            $result['isStar'] = 0;
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);

            $this->emit('cancel like practice content', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'likeUserId' => $like['userId'],
                'content' => $content,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onDisplayWallPostCreate(Event $event)
    {
        try {
            $post = $event->getSubject();

            $content = $this->getDisplayWallResultService()->getContent($post['contentId']);

            $result = $this->getDisplayWallResultService()->getResult($content['resultId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);

            $this->emit('display wall post num', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'postNum' => $content['postNum'],
            ));
        } catch (\Exception $e) {
        }
    }

    public function onTaskResultRemark(Event $event)
    {
        try {
            $result = $event->getSubject();
            $task = $this->getTaskService()->getTask($result['courseTaskId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
            $task = $this->getTaskService()->getTask($result['courseTaskId']);

            $data = array(
                'resultId' => $result['id'],
                'taskId' => $lessonTask['taskId'],
                'score' => $result['score'],
                'type' => $task['type'],
                'activityId' => $task['activityId'],
                'userIds' => array($result['userId']),
                'message' => "恭喜，回答完毕获得{$result['score']}个积分"
            );

            if (!empty($result['groupId'])) {
                $data['groupId'] = $result['groupId'];

                $activity = $this->getActivityService()->getActivity($task['activityId']);
                $config = $this->getActivityService()->getActivityConfig($task['type']);
                $brainStorm = $config->get($activity['mediaId']);

                if (!empty($brainStorm['submitWay']) && $brainStorm['submitWay'] == 'group') {
                    $groupMembers = $this->getTaskGroupService()->findTaskGroupMembersByGroupId($result['groupId']);
                    $data['userIds'] = ArrayToolkit::column($groupMembers, 'userId');
                }
            }

            $this->emit('task result remark', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", $data);
        } catch (\Exception $e) {
        }
    }

    public function onBrainStormChange(Event $event)
    {
        try {
            $brainStorm = $event->getSubject();
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($brainStorm['courseTaskId']);
            $submitWay = $this->getBrainStormWay($brainStorm['courseTaskId'], $brainStorm['activityId']);
            $user = $this->getUserService()->getUser($brainStorm['userId']);
            $brainStorm['submitWay'] = $submitWay;
            $brainStorm['userName'] = $user['truename'];
            $brainStorm['createdTime'] = isset($brainStorm['updatedTime']) ? date('Y-m-d H:i:s', $brainStorm['updatedTime']) : date('Y-m-d H:i:s', $brainStorm['createdTime']);

            if ($brainStorm['submitWay'] == "person") {
                $brainStorm['url'] = $this->generateUrl('brain_storm_group_remark', array('taskId' => $lessonTask['taskId'], 'groupId' => $brainStorm['groupId']));
                $brainStorm['replyCount'] = count($this->getBrainStormResultService()->findGroupResultsByTaskIdAndGroupId($brainStorm['courseTaskId'], $brainStorm['groupId']));
            } else {
                $brainStorm['url'] = $this->generateUrl('brain_storm_remark', array('resultId' => $brainStorm['id']));
            }

            $result =array(
                'id' => $brainStorm['id'],
                'content' => $brainStorm['content'],
                'userId' => $user['id'],
                'truename' => $user['truename'],
                'nickname' => $user['nickname'],
                'number' => $user['number'],
                'avatar' => $this->getFilePath($user['smallAvatar'], 'avatar.png'),
                'score' => 0,
            );

            $this->emit('brain storm change', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'taskId' => $lessonTask['taskId'],
                'brainStorm' => $brainStorm,
                'groupId' => $brainStorm['groupId'],
                'result' => $result,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onLessonStart(Event $event)
    {
        try {
            $lesson = $event->getSubject();

            $message = 'start lesson';
            $this->emit($message, "course-{$lesson['courseId']}-lesson-{$lesson['id']}", array());
        } catch (\Exception $e) {
        }
    }

    public function onQuestionnaireFinished(Event $event)
    {
        try {
            $data = $event->getSubject();
            $taskId = $data['taskId'];
            $questionnaireId = $data['questionnaireId'];

            $questionResults = $this->findQuestionResultsByQuestionnaireId($questionnaireId);
            $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, 'finished');
            $message = 'questionnaire finished';

            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);

            $this->emit($message, "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'questionResults' => $questionResults,
                'actualNum' => count($questionnaireResults),
            ));
        } catch (\Exception $e) {
        }
    }

    public function onLessonEnd(Event $event)
    {
        try {
            $lesson = $event->getSubject();

            $message = 'end lesson';
            $this->emit($message, "course-{$lesson['courseId']}-lesson-{$lesson['id']}", array());
        } catch (\Exception $e) {
        }
    }

    public function onLessonCancel(Event $event)
    {
        try {
            $lesson = $event->getSubject();

            $message = 'cancel lesson';
            $this->emit($message, "course-{$lesson['courseId']}-lesson-{$lesson['id']}", array());
        } catch (\Exception $e) {
        }
    }

    public function onStudentAttend(Event $event)
    {
        try {
            $signInMember = $event->getSubject();
            $lesson = $this->getCourseLessonService()->getCourseLesson($signInMember['lessonId']);
            $user = $this->getUserService()->getUser($signInMember['userId']);
            $signInMember['truename'] = $user['truename'];
            $signInMember['number'] = $user['number'];
            $signInMember['signInTime'] = date('Y-m-d H:i:s', $signInMember['updatedTime']);
            $signInMember['avatar'] = $this->getWebExtension()->getFpath($user['smallAvatar'], 'avatar.png');
            $signInMember['address'] = empty($signInMember['address']) ? '无签到位置信息。':$signInMember['address'];

            $this->emit('student attend', "course-{$lesson['courseId']}-lesson-{$lesson['id']}", $signInMember);
        } catch (\Exception $e) {
        }
    }

    public function onSignInStart(Event $event)
    {
        try {
            $signIn = $event->getSubject();
            $lesson = $this->getCourseLessonService()->getCourseLesson($signIn['lessonId']);
            $count = $this->getCourseMemberService()->countMembers(array(
                'courseId' => $lesson['courseId'],
                'role' => 'student',
            ));

            if ($signIn['time'] == 1) {
                $message = 'start first sign in';
            } else {
                $message = 'start second sign in';
            }
            $this->emit($message, "course-{$lesson['courseId']}-lesson-{$lesson['id']}", array(
                'code' => $signIn['verifyCode'],
                'signIn' => $signIn,
                'count' => $count,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onTaskStart(Event $event)
    {
        try {
            $status = $event->getSubject();
            $task = $this->getTaskService()->getTask($status['courseTaskId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($status['courseTaskId']);
            $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

            $currentTask = array(
                'activityId' => $task['activityId'],
                'activityTitle' => $task['title'],
                'activityType' => $task['type'],
                'lessonId' => $lesson['id'],
                'lessonTitle' => "课次".$lesson['number'],
                'taskId' => $status['courseTaskId'],
            );

            $this->emit("start task", "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'status' => $status,
                'task' => $task,
                'currentTask' => $currentTask
            ));
        } catch (\Exception $e) {
        }
    }

    public function onOneSentenceResultCreate(Event $event)
    {
        try {
            $result = $event->getSubject();
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
            $user = $this->getUserService()->getUser($result['userId']);
            $user['avatar'] = $this->getWebExtension()->getFpath($user['largeAvatar'], 'avatar.png');
            $group = $this->getCourseGroupService()->getCourseGroup($result['groupId']);
            $group['memberNum'] = count($this->getGroupMemberService()->findGroupMembersByGroupIdsWithUserId(array($group['id'])));
            $data = array(
                'userId' => $user['id'],
                'nickname' => $user['nickname'],
                'truename' => $user['truename'],
                'number' => $user['number'],
                'avatar' => $user['avatar'],
                'content' => $result['content'],
                'createdTime' => $result['createdTime'],
            );

            $this->emit('one sentence result', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'groupId' => $group['id'],
                'taskId' => $result['courseTaskId'],
                'replyCount' => $result['replyCount'],
                'result' => $data,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onRollcallResultCreate(Event $event)
    {
        try {
            $data = $event->getSubject();
            $result = $data['result'];
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
            $results = $this->getRollcallResultService()->findResultsByTaskId($result['courseTaskId']);

            $userIds = ArrayToolkit::column($results, 'userId');
            $randomStudentIds = $this->getCourseMemberService()->findRandomStudentIdsByLessonId($lessonTask['lessonId'], $userIds, 1);

            $this->emit('rand rollcall start', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'taskId' => $result['courseTaskId'],
                'result' => $data['selectUser'],
                'studentList' => $data['students'],
                'canRand' => !empty($randomStudentIds),
            ));
        } catch (\Exception $e) {
        }
    }

    public function onSignInCancel(Event $event)
    {
        try {
            $signIn = $event->getSubject();
            $record = $this->getLessonRecordService()->getByLessonId($signIn['lessonId']);
            $task = $this->getTaskService()->getTask($record['taskId']);
            $this->emit('cancel sign in', "course-{$record['courseId']}-lesson-{$record['lessonId']}-teachers", $signIn);

            $this->emit('cancel sign in', "course-{$record['courseId']}-lesson-{$record['lessonId']}-students",
                array(
                    'courseId' => $record['courseId'],
                    'lessonId' => $record['lessonId'],
                    'taskId' => $record['taskId'],
                    'activityType' => $task['type'],
                    'activityId' => $task['activityId'],
                )
            );
        } catch (\Exception $e) {
        }
    }

    public function onSignInEnd(Event $event)
    {
        try {
            $signIn = $event->getSubject();
            $record = $this->getLessonRecordService()->getByLessonId($signIn['lessonId']);
            $task = $this->getTaskService()->getTask($record['taskId']);

            $this->emit('end sign in', "course-{$record['courseId']}-lesson-{$record['lessonId']}-teachers", $signIn);

            $this->emit('end sign in', "course-{$record['courseId']}-lesson-{$record['lessonId']}-students",
                array(
                    'courseId' => $record['courseId'],
                    'lessonId' => $record['lessonId'],
                    'taskId' => $record['taskId'],
                    'activityType' => $task['type'],
                    'activityId' => $task['activityId'],
                    'status' => $signIn['status'],
                    'time' => $signIn['time'],
                )
            );
        } catch (\Exception $e) {
        }
    }

    public function onAttendanceSet(Event $event)
    {
        try {
            $signInMember = $event->getSubject();
            $lesson = $this->getCourseLessonService()->getCourseLesson($signInMember['lessonId']);

            $this->emit('set attendance', "course-{$lesson['courseId']}-lesson-{$lesson['id']}", $signInMember);
        } catch (\Exception $e) {
        }
    }

    public function onDisplayWallContentLike(Event $event)
    {
        try {
            $like = $event->getSubject();
            $content = $this->getDisplayWallResultService()->getContent($like['contentId']);
            $result = $this->getDisplayWallResultService()->getResult($content['resultId']);
            $result['isStar'] = 1;
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);

            $this->emit('like display wall content', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'likeUserId' => $like['userId'],
                'content' => $content,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onDisplayWallContentCancelLike(Event $event)
    {
        try {
            $like = $event->getSubject();
            $content = $this->getDisplayWallResultService()->getContent($like['contentId']);

            $result = $this->getDisplayWallResultService()->getResult($content['resultId']);
            $result['isStar'] = 0;
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);

            $this->emit('cancel like display wall content', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'likeUserId' => $like['userId'],
                'content' => $content,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onChangeDisplayWallImage(Event $event)
    {
        try {
            $content = $event->getSubject();
            $result = $this->getDisplayWallResultService()->getResult($content['resultId']);
            $content['uri'] = $this->getFilePath($content['uri'], '');
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
            $isLike = $this->getDisplayWallResultService()->isLike($content['id']);
            $result['isStar'] = $isLike ? 1 : 0;

            $this->emit('change display wall image', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'result' => $result,
                'content' => $content,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onPracticeWorkUpdate(Event $event)
    {
        try {
            $result = $event->getSubject();
            $user = $this->getUserService()->getUserProfile($result['userId']);
            $file = $this->getFileService()->getFile($result['fileId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['taskId']);
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : ($_SERVER['HTTPS'] == 'off' ? 'http://' : 'https://')).$_SERVER['HTTP_HOST'].$this->generateUrl('weixin_practice_work_picture_show', array('type' => $result['origin'], 'id' => $result['fileId']));

            $this->emit('update practice work result', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'user' => $user,
                'result' => $result,
                'url' => $url,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onPracticeWorkCreate(Event $event)
    {
        try {
            $result = $event->getSubject();
            $user = $this->getUserService()->getUserProfile($result['userId']);
            $file = $this->getFileService()->getFile($result['fileId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['taskId']);
            $url = (empty($_SERVER['HTTPS']) ? 'http://' : ($_SERVER['HTTPS'] == 'off' ? 'http://' : 'https://')).$_SERVER['HTTP_HOST'].$this->generateUrl('weixin_practice_work_picture_show', array('type' => $result['origin'], 'id' => $result['fileId']));
            if (isset($file['storage']) && $file['storage'] == 'cloud') {
                $ssl = $_SERVER['HTTPS'] == 'off' ? false : true;
                $file = $this->getMaterialLibService()->player($file['globalId'], $ssl);
                $url = $file['preview'];
            }

            $this->emit('create practice work result', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'user' => $user,
                'result' => $result,
                'url' => $url,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onDisplayWallContentCreate(Event $event)
    {
        try {
            $content = $event->getSubject();
            $result = $this->getDisplayWallResultService()->getResult($content['resultId']);
            $result['isStar'] = 0;
            $content['uri'] = $this->getFilePath($content['uri'], '');
            $user = $this->getUserService()->getUser($content['userId']);
            $user['avatar'] = $this->getFilePath($user['smallAvatar'], 'avatar.png');
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
            $routings = array(
                'contentShow' =>
                $this->generateUrl(
                    'display_wall_content_show',
                    array('contentId' => $content['id'])
                ),
                'cancelLike' =>
                $this->generateUrl(
                    'display_wall_content_cancel_like',
                    array('contentId' => $content['id'])
                ),
                'like' =>
                $this->generateUrl(
                    'display_wall_content_like',
                    array('contentId' => $content['id'])
                ),
                'remark' =>
                $this->generateUrl(
                    'display_wall_result_remark',
                    array('resultId' => $result['id'])
                ),
            );

            $this->emit('create display wall result', "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'user' => $user,
                'result' => $result,
                'content' => $content,
                'routings' => $routings,
            ));
        } catch (\Exception $e) {
        }
    }

    protected function findQuestionResultsByQuestionnaireId($questionnaireId)
    {
        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($questionnaireId, 0, PHP_INT_MAX);
        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, 'finished');
        $questionnaireResults = ArrayToolkit::index($questionnaireResults, 'id');

        $userIds = ArrayToolkit::column($questionnaireResults, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $questionnaireResultIds = ArrayToolkit::column($questionnaireResults, 'id');
        $userAnswers = $this->getQuestionService()->findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);

        if (!empty($userAnswers)) {
            $userAnswers = ArrayToolkit::group($userAnswers, 'questionId');
        }

        foreach ($questions as &$question) {
            $choices = array();
            $answers = empty($userAnswers[$question['id']]) ? array() : $userAnswers[$question['id']];
            if (in_array($question['type'], array('single_choice', 'choice'))) {
                foreach ($answers as $answer) {
                    $choices = array_merge_recursive($choices, $answer['answer']) ;
                }
                $itemCount = array_count_values($choices);

                $items = array();
                foreach ($question['metas'] as $key => $value) {
                    $items[$key]['text'] = $value;
                    $items[$key]['num'] = empty($itemCount[$key]) ? 0 : $itemCount[$key];
                    $items[$key]['part'] = empty($questionnaireResults) ? 0 : round($items[$key]['num']/count($questionnaireResults)*100, 2);
                }
                $question['items'] = $items;
                unset($question['metas']);
            } else {
                foreach ($answers as $answer) {
                    if (empty($answer['answer'][0])) {
                        continue;
                    }
                    $questionnaireResult = $questionnaireResults[$answer['questionnaireResultId']];
                    $userId = $questionnaireResult['userId'];
                    $question['answers'][$userId]['content'] = $answer['answer'][0];
                    $question['answers'][$userId]['user'] = $users[$userId]['nickname'];
                }
            }
        }
        return $questions;
    }

    public function onRaceAnswerCreate(Event $event)
    {
        try {
            $result = $event->getSubject();
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['courseTaskId']);
            $user = $this->getUserService()->getUser($result['userId']);

            $count = $this->getRaceAnswerService()->countStudentNumByTaskId($result['courseTaskId']);
            $result['count'] = $count;
            $user['avatar'] = $this->getFilePath($user['smallAvatar'], 'avatar.png');

            $result['url'] = $this->generateUrl('race_answer_result_remark', array('resultId' => $result['id']));
            $data = array(
                'avatar' => $user['avatar'],
                'nickname' => $user['nickname'],
                'truename' => $user['truename'],
                'resultId' => $result['id'],
                'score' => $result['score'],
                'userId' => $result['userId'],
                'createdTime' => date("y/m/d H:i:s", $result['createdTime'])
            );

            $this->emit("race answer result", "course-{$result['courseId']}-lesson-{$lessonTask['lessonId']}", array(
                'raceAnswer' => $result,
                'user' => $user,
                'taskId' => $result['courseTaskId'],
                'result' => $data,
            ));
        } catch (\Exception $e) {
        }
    }

    public function onTaskEnd(Event $event)
    {
        try {
            $status = $event->getSubject();
            $task = $this->getTaskService()->getTask($status['courseTaskId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($status['courseTaskId']);
            $this->emit(
                'end task',
                "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}",
                array(
                    'status' => $status,
                    'task' => $task,
                )
            );
        } catch (\Exception $e) {
        }
    }

    public function onRandomGroupJoin(Event $event)
    {
        try {
            $member = $event->getSubject();
            $task = $this->getTaskService()->getTask($member['taskId']);
            $memberCount = $this->getTaskGroupService()->countTaskGroupMembersByGroupId($member['groupId']);
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
            $captain = $this->getTaskGroupService()->getGroupCaptainByGroupId($member['groupId']);
            $user = $this->getUserService()->getUser($captain['userId']);
            $captain = $user['truename'];

            $this->emit(
                'join task group',
                "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}",
                array(
                    'groupId' => $member['groupId'],
                    'taskId' => $task['id'],
                    'activityId' => $task['activityId'],
                    'memberCount' => $memberCount,
                    'captain' => $captain,
                    'userId' => $member['userId'],
                )
            );
        } catch (\Exception $e) {
        }
    }

    public function onCourseTaskStart(Event $event)
    {
        try {
            $taskResult = $event->getSubject();
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskResult['courseTaskId']);
            $task = $this->getTaskService()->getTask($lessonTask['taskId']);

            list($next, $up) = $this->findNextAndUpActiveTask($task, $lessonTask, $taskResult);

            $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

            $activity = $this->getActivityService()->getActivity($task['activityId']);

            $currentTask = array(
                'taskId' => $task['id'],
                'activityId' => $activity['id'],
                'activityTitle' => $activity['title'],
                'activityType' => $activity['mediaType'],
                'lessonTitle' => empty($lesson['title']) ? '课次'.$lesson['number'] : $lesson['title'],
                'lessonId' => $lesson['id'],
            );

            if ($this->getCourseMemberService()->isCourseTeacher($taskResult['courseId'], $taskResult['userId'])) {
                $this->emit(
                    'course task start',
                    "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}",
                    array(
                        'taskId' => $taskResult['courseTaskId'],
                        'activityId' => $taskResult['activityId'],
                        'isVisible' => true,
                        'next' => $next,
                        'up' => $up,
                        'courseId' => $lessonTask['courseId'],
                        'lessonId' => $lessonTask['lessonId'],
                        'currentTask' => $currentTask,
                    )
                );
            }
        } catch (\Exception $e) {
        }
    }

    protected function findNextAndUpActiveTask($task, $lessonTask, $taskResult)
    {
        $baseTaskResult = $this->getTaskResultService()->getLastResultByBaseResult($taskResult);
        $baseLessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($baseTaskResult['courseTaskId']);
        $baseTask = $this->getTaskService()->getTask($baseLessonTask['taskId']);

        $nextCharpter = $this->getCourseService()->getChapter($baseLessonTask['courseId'], $baseTask['categoryId'] + 1);

        if (!empty($nextCharpte) && $nextCharpter['stage'] == 'after') {
            $next = array();
        } else {
            $next = array(
                'activityId' => $task['activityId'],
                'activityType' => $task['type'],
                'stage' => $lessonTask['stage'],
                'taskId' => $task['id'],
            );
        }

        $noSortedActiveTasksResults = $this->getTaskService()->findActiveTasksResultsByCourseId($baseTask['courseId']);
        $noSortedActivieTaskIds = ArrayToolkit::column($noSortedActiveTasksResults, 'courseTaskId');
        $noSortedActiveInLessonTasks = $this->getCourseLessonService()->findInLessonTasksByTaskIds($noSortedActivieTaskIds);
        $noSortedActiveInTasksIds = ArrayToolkit::column($noSortedActiveInLessonTasks, 'taskId');

        $charpters = $this->getCourseService()->findChapterByCourseIdAndLessonId($baseLessonTask['courseId'], $baseLessonTask['lessonId']);
        $charptersIds = ArrayToolkit::column($charpters, 'id');
        $allSortedTasks = $this->getTaskService()->findTasksByCategoryIds($charptersIds);
        $allSortedTasksIds = ArrayToolkit::column($allSortedTasks, 'id');
        $allSortedLessonTasks = $this->getCourseLessonService()->findLessonTasksByTaskIds($allSortedTasksIds);

        foreach ($allSortedLessonTasks as $key => $sortedLesosnTask) {
            if (($sortedLesosnTask['stage'] == 'in' && !in_array($sortedLesosnTask['taskId'], $noSortedActiveInTasksIds)) || $sortedLesosnTask['stage'] == 'after') {
                unset($allSortedLessonTasks[$key]);
            }
        }
        $allSortedLessonTasks = array_merge($allSortedLessonTasks);

        $arrayKey = 0;
        foreach ($allSortedLessonTasks as $key => $lessonTask) {
            if ($lessonTask['taskId'] == $baseTask['id']) {
                $arrayKey = $key;
                break;
            }
        }

        $upLessonTask = empty($allSortedLessonTasks[$arrayKey - 1]) ? array() : $allSortedLessonTasks[$arrayKey - 1];
        $up = array();
        if (!empty($upLessonTask)) {
            $upTask = $this->getTaskService()->getTask($upLessonTask['taskId']);
            $up = array(
                'activityId' => $upTask['activityId'],
                'activityType' => $upTask['type'],
                'stage' => $upLessonTask['stage'],
                'taskId' => $upTask['id'],
            );
        }

        return array($next, $up);
    }

    public function courseTaskFinish(Event $event)
    {
        try {
            $taskResult = $event->getSubject();
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskResult['courseTaskId']);

            if (!$this->getCourseMemberService()->isCourseTeacher($taskResult['courseId'], $taskResult['userId'])) {
                $this->emit(
                    'course task finish',
                    "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}",
                    array(
                        'taskId' => $taskResult['courseTaskId'],
                        'id' => $taskResult['id'],
                        'userId' => $taskResult['userId'],
                    )
                );
            }
        } catch (\Exception $e) {
        }
    }

    private function getBrainStormWay($taskId, $activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $brainStorm = $config->get($activity['mediaId']);

        return $brainStorm['submitWay'];
    }

    public function onRandomTestpaperCreate(Event $event)
    {
        try {
            $testpaper = $event->getSubject();
            if ($testpaper['doTime'] == 1) {
                $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($testpaper['taskId']);
                $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskResult['courseTaskId']);

                if (!$this->getCourseMemberService()->isCourseTeacher($taskResult['courseId'], $taskResult['userId'])) {
                    $this->emit(
                        'course task finish',
                        "course-{$lessonTask['courseId']}-lesson-{$lessonTask['lessonId']}",
                        array(
                            'taskId' => $taskResult['courseTaskId'],
                            'id' => $taskResult['id'],
                            'userId' => $taskResult['userId'],
                        )
                    );
                }
            }
        } catch (\Exception $e) {
        }
    }


    protected function getFileService()
    {
        return $this->createService('CustomBundle:File:FileService');
    }

    public function onLessonRecordChange(Event $event)
    {
        $record = $event->getSubject();
        $task = $this->getTaskService()->getTask($record['taskId']);

        try {
            $this->emit(
                'lesson teaching task record',
                "course-{$record['courseId']}-lesson-{$record['lessonId']}-students",
                array(
                    'courseId' => $record['courseId'],
                    'lessonId' => $record['lessonId'],
                    'taskId' => $record['taskId'],
                    'activityType' => $task['type'],
                    'activityId' => $task['activityId'],
                )
            );
        } catch (\Exception $e) {
        }
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    public function getBrainStormResultService()
    {
        return $this->createService('CustomBundle:Activity:BrainStormResultService');
    }

    protected function getPracticeResultService()
    {
        return $this->createService('CustomBundle:Practice:PracticeResultService');
    }

    protected function getDisplayWallResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getUserService()
    {
        return $this->createService('CustomBundle:User:UserService');
    }

    protected function getRaceAnswerService()
    {
        return $this->createService('CustomBundle:RaceAnswer:RaceAnswerService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('CustomBundle:Task:TaskResultService');
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionService');
    }

    protected function getQuestionnaireService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getLessonRecordService()
    {
        return $this->createService('CustomBundle:Lesson:RecordService');
    }

    protected function getRollcallResultService()
    {
        return $this->createService('CustomBundle:Activity:RollcallResultService');
    }
}
