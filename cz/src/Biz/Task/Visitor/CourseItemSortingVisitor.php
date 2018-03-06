<?php

namespace Biz\Task\Visitor;

use AppBundle\Common\ArrayToolkit;
use Biz\Course\Dao\CourseChapterDao;
use Biz\Course\Dao\CourseDao;
use Biz\Course\Service\CourseService;
use Biz\Task\Dao\TaskDao;
use Biz\Task\Service\TaskService;
use Biz\Task\Strategy\Impl\DefaultStrategy;
use Biz\Task\Strategy\Impl\NormalStrategy;
use Codeages\Biz\Framework\Context\Biz;
use Codeages\Biz\Framework\Dao\BatchUpdateHelper;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;

class CourseItemSortingVisitor implements CourseStrategyVisitorInterface
{
    private $biz;

    private $courseId;

    /**
     * chapter-1 / task-3
     *
     * @var string
     */
    private $itemIds;

    private $chapters;

    private $tasks;

    private $tasksGroupByChapterId;

    private $taskBatchUpdateHelper;

    private $chapterBatchUpdateHelper;

    public function __construct(Biz $biz, $courseId, $itemIds)
    {
        $this->biz = $biz;
        $this->courseId = $courseId;
        $this->itemIds = $itemIds;

        $this->init();
    }

    private function init()
    {
        $this->chapters = $this->getCourseService()->findChaptersByCourseId($this->courseId);
        $this->chapters = ArrayToolkit::index($this->chapters, 'id');

        $this->tasks = $this->getTaskService()->findTasksByCourseId($this->courseId);
        $this->tasks = ArrayToolkit::index($this->tasks, 'id');

        $this->tasksGroupByChapterId = ArrayToolkit::group($this->tasks, 'categoryId');

        $this->taskBatchUpdateHelper = new BatchUpdateHelper($this->getTaskDao());
        $this->chapterBatchUpdateHelper = new BatchUpdateHelper($this->getCourseChapterDao());
    }

    private function getChapter($chapterId)
    {
        return $this->chapters[$chapterId];
    }

    private function getTask($taskId)
    {
        return $this->tasks[$taskId];
    }

    private function getTasksByChapterId($chapterId)
    {
        return $this->tasksGroupByChapterId[$chapterId];
    }

    public function visitDefaultStrategy(DefaultStrategy $defaultStrategy)
    {
        $chapterNumber = 1;
        $unitNumber = 1;
        $needResetUnitNumber = false;
        $seq = 1;
        $taskNumber = 1;

        foreach ($this->itemIds as $itemId) {
            list($type, $chapterId) = explode('-', $itemId);

            $chapter = $this->getChapter($chapterId);
            switch ($chapter['type']) {
                case 'chapter':
                case 'unit':
                    $this->updateChapterSeq($chapter, $seq, $chapterNumber, $unitNumber, $needResetUnitNumber);
                    break;
                case 'lesson':
                    $fields['seq'] = $seq;
                    $fields['number'] = $this->updateTaskSeq($chapterId, $taskNumber, $seq);
                    $this->chapterBatchUpdateHelper->add('id', $chapterId, $fields);
                    break;
                default:
                    throw new InvalidArgumentException();
            }
        }

        $this->sync();
        $this->flush();
    }

    /**
     * 返回chapter的number，可用于判断是否全是选修
     *
     * @param $chapter
     * @param $taskNumber
     * #return string
     */
    private function updateTaskSeq($chapterId, &$taskNumber, &$seq)
    {
        $tasks = $this->getTasksByChapterId($chapterId);

        $normalTaskCount = 0;
        foreach ($tasks as $task) {
            if ($task['isOptional'] == 0) {
                ++$normalTaskCount;
            }
        }

        $taskSeqMap = array('preparation' => 1, 'lesson' => 2, 'exercise' => 3, 'homework' => 4, 'extraClass' => 5);
        //新增加的task seq不正确，重新排序
        uasort(
            $tasks,
            function ($task1, $task2) use ($taskSeqMap) {
                $seq1 = $taskSeqMap[$task1['mode']];
                $seq2 = $taskSeqMap[$task2['mode']];

                return $seq1 > $seq2;
            }
        );

        $subTaskNumber = 1;
        foreach ($tasks as $task) {
            $fields = array(
                'seq' => $seq,
                'number' => $this->getTaskNumber($taskNumber, $task, $normalTaskCount, $subTaskNumber),
            );

            $this->taskBatchUpdateHelper->add('id', $task['id'], $fields);
            ++$seq;
        }

        $chapterNumber = 0;
        if ($normalTaskCount) {
            $chapterNumber = $taskNumber;
            ++$taskNumber;
        }

        return $chapterNumber;
    }

    private function getTaskNumber($taskNumber, $task, $normalTaskCount, &$subTaskNumber)
    {
        if ($task['isOptional']) {
            return '';
        } else {
            if ($normalTaskCount == 1) {
                return $taskNumber;
            } else {
                return $taskNumber.'-'.$subTaskNumber++;
            }
        }
    }

    public function visitNormalStrategy(NormalStrategy $normalStrategy)
    {
        $chapterNumber = 1;
        $unitNumber = 1;
        $needResetUnitNumber = false;
        $seq = 1;
        $taskNumber = 1;
        foreach ($this->itemIds as $itemId) {
            list($type, $chapterIdOrTaskId) = explode('-', $itemId);

            switch ($type) {
                case 'chapter':
                    $chapter = $this->getChapter($chapterIdOrTaskId);
                    $this->updateChapterSeq($chapter, $seq, $chapterNumber, $unitNumber, $needResetUnitNumber);

                    break;
                case 'task':
                    $task = $this->getTask($chapterIdOrTaskId);
                    if ($task['isOptional']) {
                        $number = '';
                    } else {
                        $number = $taskNumber;
                        ++$taskNumber;
                    }

                    $this->taskBatchUpdateHelper->add(
                        'id',
                        $chapterIdOrTaskId,
                        array(
                            'seq' => $seq,
                            'number' => $number,
                        )
                    );

                    ++$seq;
                    break;
                default:
                    throw new InvalidArgumentException();
            }
        }

        $this->sync();
        $this->flush();
    }

    private function flush()
    {
        $this->chapterBatchUpdateHelper->flush();
        $this->taskBatchUpdateHelper->flush();
    }

    private function sync()
    {
        $this->syncTask();
        $this->syncChapter();
    }

    private function syncTask()
    {
        $copiedCourses = $this->getCourseDao()->findCoursesByParentIdAndLocked($this->courseId, 1);
        if (empty($copiedCourses)) {
            return;
        }

        $copiedCourseIds = ArrayToolkit::column($copiedCourses, 'id');
        $copiedTasks = $this->getTaskDao()->findByCopyIdSAndLockedCourseIds(
            $this->taskBatchUpdateHelper->findIdentifyKeys('id'),
            $copiedCourseIds
        );

        foreach ($copiedTasks as $copiedTask) {
            $newFields = $this->taskBatchUpdateHelper->get('id', $copiedTask['copyId']);
            $this->taskBatchUpdateHelper->add('id', $copiedTask['id'], $newFields);
        }

        unset($copiedTasks);
    }

    private function syncChapter()
    {
        $copiedCourses = $this->getCourseDao()->findCoursesByParentIdAndLocked($this->courseId, 1);
        if (empty($copiedCourses)) {
            return;
        }
        $lockedCourseIds = ArrayToolkit::column($copiedCourses, 'id');
        $copiedChapters = $this->getCourseChapterDao()->findByCopyIdsAndLockedCourseIds(
            $this->chapterBatchUpdateHelper->findIdentifyKeys('id'),
            $lockedCourseIds
        );

        foreach ($copiedChapters as $copiedChapter) {
            $newFields = $this->chapterBatchUpdateHelper->get('id', $copiedChapter['copyId']);
            $this->chapterBatchUpdateHelper->add('id', $copiedChapter['id'], $newFields);
        }

        unset($copiedChapters);
    }

    private function updateChapterSeq($chapter, &$seq, &$chapterNumber, &$unitNumber, &$needResetUnitNumber)
    {
        $fields = array(
            'seq' => $seq,
        );

        if ($needResetUnitNumber) {
            $unitNumber = 1;
            $needResetUnitNumber = false;
        }

        if ($chapter['type'] == 'chapter') {
            $fields['number'] = $chapterNumber;
            ++$chapterNumber;
            $needResetUnitNumber = true;
            ++$seq;
        }

        if ($chapter['type'] == 'unit') {
            ++$seq;
            $fields['number'] = $unitNumber;
            ++$unitNumber;
        }

        $this->chapterBatchUpdateHelper->add('id', $chapter['id'], $fields);
    }

    /**
     * @return TaskService
     */
    private function getTaskService()
    {
        return $this->biz->service('Task:TaskService');
    }

    /**
     * @return CourseService
     */
    private function getCourseService()
    {
        return $this->biz->service('Course:CourseService');
    }

    /**
     * @return TaskDao
     */
    private function getTaskDao()
    {
        return $this->biz->dao('Task:TaskDao');
    }

    /**
     * @return CourseChapterDao
     */
    private function getCourseChapterDao()
    {
        return $this->biz->dao('Course:CourseChapterDao');
    }

    /**
     * @return CourseDao
     */
    private function getCourseDao()
    {
        return $this->biz->dao('Course:CourseDao');
    }
}
