<?php

namespace CustomBundle\Biz\Course\Event;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Event\CourseSyncSubscriber;

class CustomCourseSubscriber extends CourseSyncSubscriber
{
    public static function getSubscribedEvents()
    {
        return array(
            'course.group.delete' => 'onCourseGroupDelete',
            'course.quit' => 'onCourseQuit',
            'instant.course.create' => 'onInstantCourseCreate',
            'course.lesson.cancel' => 'onCourseLessonCancel',
            'czie.chapter.delete' => 'onCzieChapterDelete',
            'course-set.closed' => 'onCourseSetClosed',
            'course.join' => 'onCourseJoin',
            'course.task.create' => 'onTaskCreate',
            'lesson.task.create' => 'onLessonTaskCreate',
            'course.lesson.end'    => 'onLessonFinish',
            'course.teachers.update' => 'onCourseTeachersUpdate',
        );
    }

    public function onCourseTeachersUpdate(Event $event)
    {
        $course = $event->getSubject();
        $teachers = $event->getArgument('teachers');

        // 老师列表中，第一个为主带老师
        $mainTeacher = $teachers[0];
        if ($course['type'] == 'instant') {
            $this->getCourseSetService()->updateCourseSetTeacherId($course['courseSetId'], $mainTeacher['id']);

            $this->getCourseMemberService()->changeCourseMainTeacher($course['id'], $mainTeacher);
        }
    }

    public function onLessonFinish(Event $event)
    {
        $courseLesson = $event->getSubject();
        $this->getReportService()->createLessonReport($courseLesson['id']);
    }

    public function onCourseGroupDelete(Event $event)
    {
        $group = $event->getSubject();
        $defaultGroup = $this->getCourseGroupService()->getDefaultGroupByCourseId($group['courseId']);

        $this->getGroupMemberService()->resetGroupMemberGroupId($group['id'], $defaultGroup['id']);
    }

    public function onCourseQuit(Event $event)
    {
        $member = $event->getArgument('member');

        $groupMember = $this->getGroupMemberService()->getGroupMemberByCourseMemberId($member['id']);
        if (!empty($groupMember)) {
            $this->getGroupMemberService()->deleteGroupMember($groupMember['id']);
        }
    }

    public function onCourseLessonCancel(Event $event)
    {
        $courseLesson = $event->getSubject();

        $lessonTasks = $this->getCourseLessonService()->findInLessonTasksByLessonId($courseLesson['id']);
        $taskIds = ArrayToolkit::column($lessonTasks, 'taskId');

        $inClassTaskIds = $this->getInclassTaskIds($taskIds);

        $this->getTaskGroupService()->deleteGroupsByTaskIds($taskIds);

        $this->getDisplayWallResultService()->deleteResultsByTaskIds($taskIds);

        $this->getOneSentenceResultService()->deleteResultsByTaskIds($taskIds);

        $this->getTaskStatusService()->deleteStatusByTaskIds($taskIds);

        $this->getRollcallResultService()->deleteResultsByTaskIds($taskIds);

        $this->getCourseTaskResultService()->deleteTaskResultsByTaskIds($taskIds);

        $this->getTestpaperService()->deleteTestpaperResultByTaskIds($taskIds);

        $this->getRaceAnswerService()->deleteResultsByTaskIds($taskIds);

        $this->getBrainStormResultService()->deleteResultsByTaskIds($taskIds);

        $this->getQuestionnaireService()->deleteQuestionnaireResultByTaskIds($taskIds);

        $this->getActivityLearnLogService()->deleteLearnLogsByTaskId($taskIds);

        $this->getScoreService()->deleteScoresByTaskIds($taskIds);

        $this->getPracticeWorkService()->deleteResultsByTaskIds($inClassTaskIds);

        $this->getPracticeResultService()->cancelCourseLessonProcess($inClassTaskIds);

        $this->getSignInService()->deleteSignInsByLessonId($courseLesson['id']);
    }

    public function onCzieChapterDelete(Event $event)
    {
        $chapter = $event->getSubject();
        $course = $this->getCourseService()->getCourse($chapter['courseId']);
        if ($course['type'] == 'instant') {
            $this->getCourseService()->deleteChapter($chapter['courseId'], $chapter['categoryId']);
        }
    }

    public function onCourseSetClosed(Event $event)
    {
        $courseSet = $event->getSubject();
        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSet['id']);
        foreach ($courses as $key => $course) {
            if ($course['status'] == 'published') {
                $this->getCourseService()->closeCourse($course['id']);
            }
        }
    }

    public function onInstantCourseCreate(Event $event)
    {
        $course = $event->getSubject();

        $this->getCourseGroupService()->createCourseGroup(array(
            'title' => '未分组',
            'courseId' => $course['id'],
            'type' => 'default',
        ));
    }

    public function onCourseJoin(Event $event)
    {
        $course = $event->getSubJect();
        if ($course['type'] == 'instant') {
            $userId = $event->getArgument('userId');
            $member = $this->getCourseMemberService()->getCourseMember($course['id'], $userId);

            $courseGroup = $this->getCourseGroupService()->getDefaultGroupByCourseId($course['id']);
            $groupMember = $this->getGroupMemberService()->getMaxSeqByGroupId($courseGroup['id']);
            $seq = empty($groupMember['seq']) ? 0 : $groupMember['seq'];
            $this->getGroupMemberService()->createGroupMember(array(
                'groupId' => $courseGroup['id'],
                'courseMemberId' => $member['id'],
                'seq' => $seq + 1
            ));
        }
    }

    public function onTaskCreate(Event $event)
    {
        $task = $event->getSubject();
        $course = $this->getCourseService()->getCourse($task['courseId']);

        $log = $this->getPrepareCourseLogService()->getLogByCourseId($course['id']);
        if ($course['type'] == 'instant' && empty($log)) {
            $this->getPrepareCourseLogService()->createLog($course['id']);
        }
    }

    public function onLessonTaskCreate(Event $event)
    {
        $lessonTask = $event->getSubject();
        $stageStr = array(
            'before' => '课前任务创建',
            'in' => '课中任务创建',
            'after' => '课后任务创建',
        );

        $courseLesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        $this->getCourseLessonService()->taskNumByLessonId($courseLesson['id'], ++$courseLesson['taskNum']);
        $course = $this->getCourseService()->getCourse($lessonTask['courseId']);

        $isGain = $this->getTeacherScoreService()->isGainScoreByLessonIdAndSource($lessonTask['lessonId'], $lessonTask['stage']);
        if ($isGain || $lessonTask['isCopy']) {
            return ;
        }

        $teacherScore = array(
            'lessonId' => $courseLesson['id'],
            'courseId' => $courseLesson['courseId'],
            'type' => 'auto',
            'term' => $course['termCode'],
            'score' => 1,
            'source' => $lessonTask['stage'],
            'remark' => "课程: {$course['title']}-课次{$courseLesson['number']}-{$stageStr[$lessonTask['stage']]}",
        );

        $this->getTeacherScoreService()->createTeacherScore($teacherScore);
    }

    private function getInclassTaskIds($taskIds)
    {
        $lessonTasks = $this->getCourseLessonService()->findLessonTasksByTaskIds($taskIds);
        $lessonTasks = ArrayToolkit::group($lessonTasks, 'stage');
        if (isset($lessonTasks['in'])) {
            return ArrayToolkit::column($lessonTasks['in'], 'taskId');
        }
        return array();
    }

    protected function getCourseSetService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseSetService');
    }

    protected function getOneSentenceResultService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:OneSentenceResultService');
    }

    protected function getCourseGroupService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseGroupService');
    }

    protected function getLogService()
    {
        return $this->getBiz()->service('System:LogService');
    }

    protected function getRollcallResultService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:RollcallResultService');
    }

    protected function getTaskGroupService()
    {
        return $this->getBiz()->service('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getDisplayWallResultService()
    {
        return $this->getBiz()->service('CustomBundle:DisplayWall:ResultService');
    }

    protected function getPracticeWorkService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:PracticeWorkService');
    }

    protected function getBrainStormResultService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:BrainStormResultService');
    }

    protected function getTaskStatusService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskStatusService');
    }

    protected function getGroupMemberService()
    {
        return $this->getBiz()->service('CustomBundle:Course:GroupMemberService');
    }

    protected function getSignInService()
    {
        return $this->getBiz()->service('CustomBundle:SignIn:SignInService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseService');
    }

    protected function getPrepareCourseLogService()
    {
        return $this->getBiz()->service('CustomBundle:Course:PrepareCourseLogService');
    }

    protected function getCourseMemberService()
    {
        return $this->getBiz()->service('CustomBundle:Course:MemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->getBiz()->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseTaskResultService()
    {
        return $this->getBiz()->service('CustomBundle:Task:TaskService');
    }

    protected function getTestpaperService()
    {
        return $this->getBiz()->service('CustomBundle:Testpaper:TestpaperService');
    }

    protected function getRaceAnswerService()
    {
        return $this->getBiz()->service('CustomBundle:RaceAnswer:RaceAnswerService');
    }

    protected function getTeacherScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:TeacherScoreService');
    }

    protected function getQuestionnaireService()
    {
        return $this->getBiz()->service('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('CustomBundle:Activity:ActivityLearnLogService');
    }

    protected function getScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:ScoreService');
    }

    protected function getReportService()
    {
        return $this->getBiz()->service('CustomBundle:Report:StudentLessonReportService');
    }

    protected function getPracticeResultService()
    {
        return $this->getBiz()->service('CustomBundle:Practice:PracticeResultService');
    }
}
