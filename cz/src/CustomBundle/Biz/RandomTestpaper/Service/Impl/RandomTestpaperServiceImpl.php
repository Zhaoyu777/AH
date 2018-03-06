<?php

namespace CustomBundle\Biz\RandomTestpaper\Service\Impl;

use Biz\BaseService;
use CustomBundle\Biz\RandomTestpaper\Service\RandomTestpaperService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;

class RandomTestpaperServiceImpl extends BaseService implements RandomTestpaperService
{
    public function createTestpaper($testpaper)
    {
        if (!ArrayToolkit::requireds($testpaper, array('courseId', 'taskId', 'activityId', 'userId'))) {
            throw $this->createInvalidArgumentException('缺少必要字段');
        }

        $testpaper = ArrayToolkit::parts($testpaper, array(
            'courseId',
            'lessonId',
            'taskId',
            'activityId',
            'userId',
            'score',
            'status',
            'passedScore',
        ));

        $lastTestpaper = $this->getLastTestpaperByTaskIdAndUserId($testpaper['taskId'], $testpaper['userId']);

        if (!empty($lastTestpaper)) {
            $testpaper['doTime'] = ++$lastTestpaper['doTime'];
        }

        $this->beginTransaction();
        try {
            $created = $this->getRandomTestpaperDao()->create($testpaper);
            $this->dispatch('random.testpaper.create', $created);

            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getLastTestpaperByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getRandomTestpaperDao()->getLastTestpaperByTaskIdAndUserId($taskId, $userId);
    }

    public function getTestpaper($testId)
    {
        return $this->getRandomTestpaperDao()->get($testId);
    }

    public function findTestpapersByTaskIdAndDoTime($taskId, $doTime = 1)
    {
        return $this->getRandomTestpaperDao()->findByTaskIdAndDoTime($taskId, $doTime);
    }

    public function findMaxScoreAndTimsByTaskIdGroupByUserId($taskId)
    {
        return ArrayToolkit::index($this->getRandomTestpaperDao()->findMaxScoreAndTimsByTaskIdGroupByUserId($taskId), 'userId');
    }

    public function buildExportData($courseId, $taskId)
    {
        $courseMembers = $this->getCourseMemberService()->findCourseStudents($courseId, 0, PHP_INT_MAX);
        $userIds = ArrayToolkit::column($courseMembers, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $testpapers = $this->findTestpapersByTaskIdAndDoTime($taskId);
        $testpapers = ArrayToolkit::index($testpapers, 'userId');
        $maxScores = $this->findMaxScoreAndTimsByTaskIdGroupByUserId($taskId);

        foreach ($testpapers as &$testpaper) {
            $testpaper['maxScore'] = $maxScores[$testpaper['userId']]['score'];
            $testpaper['times'] = $maxScores[$testpaper['userId']]['doTimes'];
            $testpaper['truename'] = $users[$testpaper['userId']]['truename'];
            $testpaper['number'] = $users[$testpaper['userId']]['number'];
            $testpaper['testTime'] = date('Y-m-d', $testpaper['createdTime']);
        }

        foreach ($users as $userId => $user) {
            if (empty($testpapers[$userId])) {
                $testpapers[$userId] = array(
                    'truename' => $user['truename'],
                    'number' => $user['number'],
                    'times' => '0',
                    'score' => '--',
                    'testTime' => '--',
                    'maxScore' => '--',
                );
            }
        }

        return $testpapers;
    }

    public function createTestpaperItem($item)
    {
        if (!ArrayToolkit::requireds($item, array('testId', 'questionId', 'seq'))) {
            throw $this->createInvalidArgumentException('缺少必要字段');
        }

        $item = ArrayToolkit::parts($item, array(
            'testId',
            'questionId',
            'realScore',
            'answer',
            'score',
            'status',
            'seq',
            'questionType',
            'missScore',
        ));

        return $this->getRandomTestpaperItemDao()->create($item);
    }

    public function showTestpaperItems($taskId, $userId)
    {
        $testpaper = $this->getLastTestpaperByTaskIdAndUserId($taskId, $userId);

        $testpaperBuilder = $this->getRandomTestpaperBuilder();

        return $testpaperBuilder->showTestItems($testpaper['id']);
    }

    public function findTestpaperItemsByTestId($testId)
    {
        return $this->getRandomTestpaperItemDao()->findByTestId($testId);
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getRandomTestpaperDao()
    {
        return $this->createDao('CustomBundle:RandomTestpaper:RandomTestpaperDao');
    }

    protected function getRandomTestpaperItemDao()
    {
        return $this->createDao('CustomBundle:RandomTestpaper:RandomTestpaperItemDao');
    }

    public function getRandomTestpaperBuilder()
    {
        return $this->biz["testpaper_builder.randomTestpaper"];
    }
}
