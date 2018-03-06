<?php

namespace Biz\OpenCourse\Event;

use Biz\Taxonomy\TagOwnerManager;
use Codeages\Biz\Framework\Event\Event;
use Codeages\PluginBundle\Event\EventSubscriber;

class OpenCourseEventSubscriber extends EventSubscriber
{
    public static function getSubscribedEvents()
    {
        return array(
            'open.course.update' => 'onCourseUpdate',
            'open.course.delete' => 'onCourseDelete',
            'open.course.lesson.create' => 'onLessonCreate',
            'open.course.lesson.delete' => 'onLessonDelete',
            'open.course.member.create' => 'onMemberCreate',
            'course.material.create' => 'onMaterialCreate',
            'course.material.update' => 'onMaterialUpdate',
            'course.material.delete' => 'onMaterialDelete',
            'live.replay.generate' => 'onLiveReplayGenerate',
        );
    }

    public function onCourseDelete(Event $event)
    {
        $course = $event->getSubject();

        $tagOwnerManager = new TagOwnerManager('openCourse', $course['id']);
        $tagOwnerManager->delete();
    }

    public function onCourseUpdate(Event $event)
    {
        $fields = $event->getSubject();

        $course = $fields['course'];
        $tagIds = $fields['tagIds'];
        $userId = $fields['userId'];

        $tagOwnerManager = new TagOwnerManager('openCourse', $course['id'], $tagIds, $userId);
        $tagOwnerManager->update();
    }

    public function onLessonCreate(Event $event)
    {
        $context = $event->getSubject();
        $lesson = $context['lesson'];

        $course = $this->getOpenCourseService()->getCourse($lesson['courseId'], true);

        if (empty($course)) {
            throw new \RuntimeException('添加课时失败，课程不存在。');
        }

        if ($course['status'] === 'draft' || $lesson['type'] === 'liveOpen') {
            $this->getOpenCourseService()->publishLesson($course['id'], $lesson['id']);
        }

        $lessonNum = $this->getOpenCourseService()->countLessons(array('courseId' => $lesson['courseId']));
        $this->getOpenCourseService()->updateCourse($lesson['courseId'], array('lessonNum' => $lessonNum));
    }

    public function onLessonDelete(Event $event)
    {
        $context = $event->getSubject();
        $lesson = $context['lesson'];

        $lessonNum = $this->getOpenCourseService()->countLessons(array('courseId' => $lesson['courseId']));
        $this->getOpenCourseService()->updateCourse($lesson['courseId'], array('lessonNum' => $lessonNum));
    }

    public function onMemberCreate(Event $event)
    {
        $context = $event->getSubject();
        $fields = $context['argument'];
        $member = $context['newMember'];

        $memberNum = $this->getOpenCourseService()->countMembers(array('courseId' => $fields['courseId']));

        $this->getOpenCourseService()->updateCourse($fields['courseId'], array('studentNum' => $memberNum));
    }

    public function onMaterialCreate(Event $event)
    {
        $material = $event->getSubject();

        if ($material && $material['lessonId'] && $material['source'] == 'opencoursematerial' && $material['type'] == 'openCourse') {
            $this->getOpenCourseService()->waveCourseLesson($material['lessonId'], 'materialNum', 1);
        }
    }

    public function onMaterialUpdate(Event $event)
    {
        $material = $event->getSubject();
        $argument = $event->getArgument('argument');

        $lesson = $this->getOpenCourseService()->getCourseLesson($material['courseId'], $material['lessonId']);

        if ($material['source'] == 'opencoursematerial') {
            if ($material['lessonId']) {
                $this->getOpenCourseService()->waveCourseLesson($material['lessonId'], 'materialNum', 1);
            } elseif ($material['lessonId'] == 0 && isset($argument['lessonId']) && $argument['lessonId']) {
                $material['lessonId'] = $argument['lessonId'];
                $this->_waveLessonMaterialNum($material);
            }
        }
    }

    public function onMaterialDelete(Event $event)
    {
        $material = $event->getSubject();

        $lesson = $this->getOpenCourseService()->getCourseLesson($material['courseId'], $material['lessonId']);

        if ($lesson) {
            if ($material['lessonId'] && $material['source'] == 'opencourselesson' && $material['type'] == 'openCourse') {
                $this->getOpenCourseService()->resetLessonMediaId($material['lessonId']);
            }

            if ($material['lessonId'] && $material['source'] == 'opencoursematerial' && $material['type'] == 'openCourse') {
                $this->getOpenCourseService()->waveCourseLesson($material['lessonId'], 'materialNum', -1);
            }
        }
    }

    public function onLiveReplayGenerate(Event $event)
    {
        $replays = $event->getSubject();

        if (!$replays) {
            return;
        }

        $replay = current($replays);

        if ($replay['type'] != 'liveOpen') {
            return;
        }

        $courseId = $replay['courseId'];
        $lessonId = $replay['lessonId'];

        $lessonFields = array(
            'replayStatus' => 'generated',
        );

        $this->getOpenCourseService()->updateLesson($courseId, $lessonId, $lessonFields);
    }

    private function _waveLessonMaterialNum($material)
    {
        if ($material['lessonId'] && $material['source'] == 'opencoursematerial' && $material['type'] == 'openCourse') {
            $count = $this->getMaterialService()->countMaterials(array(
                    'courseId' => $material['courseId'],
                    'lessonId' => $material['lessonId'],
                    'source' => 'opencoursematerial',
                    'type' => 'openCourse',
                )
            );
            $this->getOpenCourseService()->updateLesson($material['courseId'], $material['lessonId'], array('materialNum' => $count));

            return true;
        }

        return false;
    }

    protected function getNoteService()
    {
        return $this->getBiz()->service('Course:CourseNoteService');
    }

    protected function getOpenCourseService()
    {
        return $this->getBiz()->service('OpenCourse:OpenCourseService');
    }

    protected function getMaterialService()
    {
        return $this->getBiz()->service('Course:MaterialService');
    }
}
