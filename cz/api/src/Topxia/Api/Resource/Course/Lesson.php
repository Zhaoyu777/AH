<?php

namespace Topxia\Api\Resource\Course;

use AppBundle\Common\ArrayToolkit;
use Biz\Accessor\AccessorInterface;
use Biz\Course\Service\CourseService;
use Silex\Application;
use AppBundle\Common\SettingToolkit;
use Topxia\Api\Resource\BaseResource;
use Symfony\Component\HttpFoundation\Request;

class Lesson extends BaseResource
{
    public function get(Application $app, Request $request, $id)
    {
        $task = $this->getTaskService()->getTask($id);

        if (empty($task)) {
            return $this->error('not_courseId', "ID为#{$id}的课时不存在");
        }

        $access = $this->getCourseService()->canLearnTask($task['id']);

        $isTrail = false;
        if (!($access['code'] == AccessorInterface::SUCCESS || $isTrail = $access['code'] == 'allow_trial')) {
            return $this->error($access['code'], $access['msg']);
        }

        $course = $this->getCourseService()->getCourse($task['courseId']);

        $lesson = $this->getCourseService()->convertTasks(array($task), $course);
        $lesson = array_shift($lesson);

        //直播回放
        if ($lesson['type'] == 'live' && $lesson['replayStatus'] == 'videoGenerated') {
            $lesson['type'] = 'video';
        }

        $currentUser = $this->getCurrentUser();

        if ($currentUser->isLogin()) {
            $this->getTaskService()->startTask($lesson['id']);
        }

        if ($line = $request->query->get('line')) {
            $lesson['hlsLine'] = $line;
        }
        $hls_encryption = $request->query->get('hls_encryption');
        $enable_hls_encryption_plus = SettingToolkit::getSetting('storage.enable_hls_encryption_plus');

        if (!empty($hls_encryption) && $enable_hls_encryption_plus) {
            $lesson['hlsEncryption'] = true;
        }

        $ssl = $request->isSecure() ? true : false;

        $lesson = $this->filter($this->convertLessonContent($lesson, $ssl, $isTrail));

        $hasRemainTime = $this->hasRemainTime($lesson, $task['type']);
        if ($hasRemainTime && $currentUser->isLogin()) {
            $remainTime = $this->getRemainTime($currentUser, $lesson);
            $lesson['remainTime'] = $remainTime;
        }

        return $lesson;
    }

    public function filter($lesson)
    {
        $lesson['createdTime'] = date('c', $lesson['createdTime']);
        $lesson['updatedTime'] = date('c', $lesson['updatedTime']);

        return $lesson;
    }

    protected function convertLessonContent($lesson, $ssl = false, $isTrail = false)
    {
        switch ($lesson['type']) {
            case 'ppt':
                return $this->getPPTLesson($lesson, $ssl);
            case 'audio':
                return $this->getAudioLesson($lesson, $ssl);
            case 'video':
                return $this->getVideoLesson($lesson, $isTrail);
            case 'testpaper':
                return $this->getTestpaperLesson($lesson);
            case 'document':
                return $this->getDocumentLesson($lesson, $ssl);
            default:
                return $this->getTextLesson($lesson);
        }
    }

    protected function getPPTLesson($lesson, $ssl = false)
    {
        $file = $this->getUploadFileService()->getFullFile($lesson['mediaId']);

        if (empty($file)) {
            return $this->error('not_ppt', '文件不存在');
        }

        if ($file['convertStatus'] == 'error') {
            return $this->error('not_ppt', 'PPT文档转换失败，请到课程文件管理中，重新转换');
        }

        if ($file['convertStatus'] != 'success') {
            return $this->error('not_ppt', 'PPT文档还在转换中，还不能查看，请稍等');
        }

        $result = $this->getMaterialLibService()->player($file['globalId'], $ssl);

        $lesson['content'] = array(
            'resource' => $result['images'],
        );

        return $lesson;
    }

    protected function getDocumentLesson($lesson, $ssl = false)
    {
        $file = $this->getUploadFileService()->getFullFile($lesson['mediaId']);
        if (empty($file)) {
            return $this->error('not_document', '文件不存在');
        }

        if ($file['convertStatus'] == 'error') {
            return $this->error('not_document', '文档转换失败，请联系管理员');
        }

        if ($file['convertStatus'] != 'success') {
            return $this->error('not_document', '文档还在转换中，还不能查看，请稍等');
        }

        $result = $this->getMaterialLibService()->player($file['globalId'], $ssl);

        $resourceUrl = ($ssl ? 'https://' : 'http://').$_SERVER['HTTP_HOST']."/global_file/{$file['globalId']}/player?token={$result['token']}";

        $lesson['content'] = array(
            'resource' => $resourceUrl,
            'previewUrl' => $resourceUrl,
        );

        return $lesson;
    }

    protected function getAudioLesson($lesson, $ssl = false)
    {
        $file = $this->getUploadFileService()->getFullFile($lesson['mediaId']);
        if (empty($file)) {
            return $this->error('not_audio', "文件不存在");
        }

        $result = $this->getMaterialLibService()->player($file['globalId'], $ssl);
        $lesson['mediaUri'] = $result['url'];

        return $lesson;
    }

    protected function getTestpaperLesson($lesson)
    {
        $user = $this->getCurrentUser();

        $activity = $this->getActivityService()->getActivity($lesson['activityId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperActivity['mediaId'], $activity['mediaType']);
        if (empty($testpaper)) {
            return $this->error('error', '试卷不存在!');
        }

        $course = $this->getCourseService()->getCourse($lesson['courseId']);

        $testResult = $this->getTestpaperService()->getUserLatelyResultByTestId($user['id'], $testpaper['id'], $lesson['courseId'], $lesson['activityId'], 'testpaper');

        $lesson['content'] = array(
            'status' => empty($testResult) ? 'nodo' : $testResult['status'],
            'resultId' => empty($testResult) ? 0 : $testResult['id'],
        );

        return $lesson;
    }

    private function getTextLesson($lesson)
    {
        $lesson['content'] = $this->filterHtml($lesson['content']);
        $template = $this->render('course/lesson-text-content.html.twig', array(
            'content' => $lesson['content'],
        ));
        $lesson['content'] = $template;

        return $lesson;
    }

    protected function getVideoLesson($lesson, $isTrail = false)
    {
        $line = empty($lesson['hlsLine']) ? '' : $lesson['hlsLine'];
        $hlsEncryption = (!empty($lesson['hlsEncryption']) && true === $lesson['hlsEncryption']);
        $mediaSource = $lesson['mediaSource'];
        $mediaUri = $lesson['mediaUri'];

        $watchTimeLimit = 0;

        if ($isTrail) {
            $course = $this->getCourseService()->getCourse($lesson['courseId']);
            $watchTimeLimit = $course['tryLookLength'] * 60;
        }

        if ($mediaSource == 'self') {
            $file = $this->getUploadFileService()->getFullFile($lesson['mediaId']);
            
            if (!empty($file)) {
                $lesson['mediaStorage'] = $file['storage'];
                if ($file['storage'] == 'cloud') {
                    $lesson['mediaConvertStatus'] = $file['convertStatus'];

                    if (!empty($file['metas2']) && !empty($file['metas2']['sd']['key'])) {
                        if (isset($file['convertParams']['convertor']) && ($file['convertParams']['convertor'] == 'HLSEncryptedVideo')) {
                            $headLeaderInfo = $this->getHeadLeaderInfo();

                            if ($headLeaderInfo) {
                                $token = $this->getTokenService()->makeToken('hls.playlist', array(
                                    'data' => array(
                                        'id' => $headLeaderInfo['id'],
                                        'fromApi' => !$hlsEncryption,
                                    ),
                                    'times' => 2,
                                    'duration' => 3600,
                                ));

                                $headUrl = array(
                                    'url' => $this->getHttpHost()."/hls/{$headLeaderInfo['id']}/playlist/{$token['token']}.m3u8?format=json&line=".$line,
                                );

                                $lesson['headUrl'] = $headUrl['url'];
                            }

                            $data = array(
                                'id' => $file['id'],
                                'fromApi' => !$hlsEncryption,
                            );
                            if ($watchTimeLimit) {
                                $data['watchTimeLimit'] = $watchTimeLimit;
                            }

                            $token = $this->getTokenService()->makeToken('hls.playlist', array(
                                'data' => $data,
                                'times' => 2,
                                'duration' => 3600,
                            ));

                            $url = array(
                                'url' => $this->getHttpHost()."/hls/{$file['id']}/playlist/{$token['token']}.m3u8?format=json&line=".$line,
                            );
                        } else {
                            return $this->error('404', '当前视频格式不能被播放！');
                        }

                        $lesson['mediaUri'] = (isset($url) && is_array($url) && !empty($url['url'])) ? $url['url'] : '';
                    } else {
                        if (!empty($file['metas']) && !empty($file['metas']['hd']['key'])) {
                            $key = $file['metas']['hd']['key'];
                        } else {
                            if ($file['type'] == 'video') {
                                $key = null;
                            } else {
                                $key = $file['hashId'];
                            }
                        }

                        if ($key) {
                            return $this->error('404', '当前视频格式不能被播放！');
                        } else {
                            $lesson['mediaUri'] = '';
                        }
                    }
                } else {
                    $token = $this->getTokenService()->makeToken('local.media', array(
                        'data' => array(
                            'id' => $file['id'],
                        ),
                        'duration' => 3600,
                        'userId' => 0,
                    ));
                    $lesson['mediaUri'] = $this->getHttpHost()."/player/{$file['id']}/file/{$token['token']}";
                }
            } else {
                $lesson['mediaUri'] = '';
            }
        } elseif ($mediaSource == 'youku') {
            $matched = preg_match('/\/sid\/(.*?)\/v\.swf/s', $lesson['mediaUri'], $matches);

            if ($matched) {
                $lesson['mediaUri'] = "http://player.youku.com/embed/{$matches[1]}";
            } else {
                $lesson['mediaUri'] = '';
            }
        } elseif ($mediaSource == 'tudou') {
            $matched = preg_match('/\/v\/(.*?)\/v\.swf/s', $lesson['mediaUri'], $matches);

            if ($matched) {
                $lesson['mediaUri'] = "http://www.tudou.com/programs/view/html5embed.action?code={$matches[1]}";
            } else {
                $lesson['mediaUri'] = '';
            }
        } else {
            $lesson['mediaUri'] = $mediaUri;
        }

        return $lesson;
    }

    protected function getHeadLeaderInfo()
    {
        $storage = $this->getSettingService()->get('storage');

        if (!empty($storage) && array_key_exists('video_header', $storage) && $storage['video_header']) {
            $file = $this->getUploadFileService()->getFileByTargetType('headLeader');

            return $file;
        }

        return false;
    }

    protected function simplify($res)
    {
        $lesson = array();
        $lesson['id'] = $res['id'];
        $lesson['courseId'] = $res['courseId'];
        $lesson['courseSetId'] = $res['fromCourseSetId'];
        $lesson['chapterId'] = $res['categoryId'];
        $lesson['number'] = $res['number'];
        $lesson['seq'] = $res['seq'];
        $lesson['free'] = $res['isFree'];
        $lesson['title'] = $res['title'];
        $lesson['summary'] = $res['summary'];
        $lesson['type'] = $res['type'];
        $lesson['content'] = $res['content'];
        $lesson['mediaId'] = $res['mediaId'];
        $lesson['learnedNum'] = $res['learnedNum'];
        $lesson['viewedNum'] = $res['viewedNum'];
        $lesson['giveCredit'] = $res['giveCredit'];
        $lesson['requireCredit'] = $res['requireCredit'];
        $lesson['length'] = $res['length'];
        $lesson['userId'] = $res['userId'];
        $lesson['createdTime'] = $res['createdTime'];
        $lesson['updatedTime'] = $res['updatedTime'];

        return $lesson;
    }

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function hasRemainTime($task, $taskType)
    {
        if ('video' != $task['type'] || $taskType == 'live') {
            return false;
        }

        $course = $this->getCourseService()->getCourse($task['courseId']);
        if (empty($course['watchLimit'])) {
            return false;
        }

        $isLimit = SettingToolkit::getSetting('magic.lesson_watch_limit');
        if (!$isLimit) {
            return false;
        }

        return true;
    }

    protected function getRemainTime($user, $lesson)
    {
        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($lesson['id']);

        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $remainTime = ($course['watchLimit'] * $lesson['length']) - $taskResult['watchTime'];
        return $remainTime;
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    protected function getMaterialLibService()
    {
        return $this->createService('MaterialLib:MaterialLibService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }
}
