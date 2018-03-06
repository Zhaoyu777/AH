<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\Impl\ReportServiceImpl as BaseReportServiceImpl;

class ReportServiceImpl extends BaseReportServiceImpl
{
    public function getCourseTaskLearnStat($courseId)
    {
        $tasks = $this->getTaskService()->findTasksByCourseId($courseId);

        foreach ($tasks as &$task) {
            if ($task['status'] !== 'published') {
                continue;
            }

            $task['alias'] = $task['number'] ? '任务'.$task['number'] : '选修任务';

            $task['finishedNum'] = $this->getTaskResultService()->countUsersByTaskIdAndLearnStatus($task['id'], 'finish');
            $task['learnNum'] = $this->getTaskResultService()->countUsersByTaskIdAndLearnStatus($task['id'], 'start');

            if ($task['learnNum']) {
                $task['finishedRate'] = round($task['finishedNum'] / ($task['learnNum'] + $task['finishedNum']), 3) * 100;
            } else {
                $task['finishedRate'] = 0;
            }
        }

        return array_reverse($tasks);
    }

    protected function getLatestMonthData($courseId, $now)
    {
        $startTimeGreaterThan = strtotime('- 29 days', $now);
        $role = 'student';
        $result = array();

        $students = $this->getCourseMemberService()->searchMembers(
            array(
                'courseId' => $courseId,
                'role' => $role,
                'startTimeGreaterThan' => $startTimeGreaterThan,
            ),
            array('createdTime' => 'ASC'),
            0,
            PHP_INT_MAX
        );

        $userFinishedTimes = $this->getTaskResultService()->findFinishedTimeByCourseIdGroupByUserId($courseId);

        if (!empty($students) && !empty($userFinishedTimes)) {
            $userFinishedTimes = ArrayToolkit::index($userFinishedTimes, 'userId');
            foreach ($students as &$student) {
                if (!empty($userFinishedTimes[$student['userId']])) {
                    $student['finished'] = $userFinishedTimes[$student['userId']]['finishedTime'];
                }
            }
        }

        $result['students'] = $students;

        $result['notes'] = $this->getCourseNoteService()->searchNotes(
            array(
                'courseId' => $courseId,
                'startTimeGreaterThan' => $startTimeGreaterThan,
            ),
            array('createdTime' => 'ASC'),
            0,
            PHP_INT_MAX
        );

        $result['asks'] = $this->getThreadService()->searchThreads(
            array(
                'courseId' => $courseId,
                'type' => 'question',
                'startTimeGreaterThan' => $startTimeGreaterThan,
            ),
            array(),
            0,
            PHP_INT_MAX
        );

        $result['discussions'] = $this->getThreadService()->searchThreads(
            array(
                'courseId' => $courseId,
                'type' => 'discussion',
                'startTimeGreaterThan' => $startTimeGreaterThan,
            ),
            array(),
            0,
            PHP_INT_MAX
        );

        return $result;
    }

    protected function getAMonthAgoStatCount($courseId, $now)
    {
        $role = 'student';
        $startTimeLessThan = strtotime('- 29 days', $now);
        $result = array();

        //学员数
        $result['studentNum'] = $this->getCourseMemberService()->countMembers(array(
            'courseId' => $courseId,
            'role' => $role,
            'startTimeLessThan' => $startTimeLessThan,
        ));

        //完成数
        $result['finishedNum'] = $this->countMembersFinishedAllTasksByCourseId($courseId, $startTimeLessThan);

        //完成率
        if ($result['studentNum']) {
            $result['finishedRate'] = round($result['finishedNum'] / $result['studentNum'], 3) * 100;
        } else {
            $result['finishedRate'] = 0;
        }

        //笔记数
        $result['noteNum'] = $this->getCourseNoteService()->countCourseNotes(array(
            'courseId' => $courseId,
            'startTimeLessThan' => $startTimeLessThan,
        ));

        //问题数
        $result['askNum'] = $this->getThreadService()->countThreads(array(
            'courseId' => $courseId,
            'type' => 'question',
            'startTimeLessThan' => $startTimeLessThan,
        ));

        //讨论数
        $result['discussionNum'] = $this->getThreadService()->countThreads(array(
            'courseId' => $courseId,
            'type' => 'discussion',
            'startTimeLessThan' => $startTimeLessThan,
        ));

        return $result;
    }

    protected function countMembersFinishedAllTasksByCourseId($courseId, $finishedTimeLessThan = '')
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $condition = array(
            'role' => 'student',
            'learnedNumGreaterThan' => $course['compulsoryTaskNum'],
            'courseId' => $courseId,
        );

        if (!empty($finishedTimeLessThan)) {
            $condition['finishedTime_LE'] = $finishedTimeLessThan;
        }
        $memberCount = $this->getCourseMemberService()->countMembers($condition);

        return $memberCount;
    }
}