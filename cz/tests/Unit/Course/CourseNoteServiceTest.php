<?php

namespace Tests\Unit\Course;

use Biz\Task\Service\TaskService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseNoteService;
use Biz\BaseTestCase;
use Biz\Course\Service\CourseSetService;

class NoteServiceTest extends BaseTestCase
{
    public function testGetNote()
    {
        $note = $this->createNote();
        $foundNote = $this->getNoteService()->getNote($note['id']);
        $this->assertEquals($note['userId'], $foundNote['userId']);
    }

    public function testGetNoteWithNotExistNote()
    {
        $result = $this->getNoteService()->getNote(999);
        $this->assertEquals(false, $result);
    }

    public function testGetUserLessonNote()
    {
        $note = $this->createNote();

        $foundNote = $this->getNoteService()->getCourseNoteByUserIdAndTaskId($note['userId'], $note['taskId']);

        $this->assertEquals($note['id'], $foundNote['id']);
        $this->assertEquals($note['userId'], $foundNote['userId']);
        $this->assertEquals($note['taskId'], $foundNote['taskId']);
    }

    public function testsearchNoteCount()
    {
        $user = $this->getCurrentUser();
        $courseSet = $this->createCourseSet();
        $course = $this->getCourseService()->getDefaultCourseByCourseSetId($courseSet['id']);

        $task1 = $this->getTaskService()->createTask(array(
            'fromCourseId' => $course['id'],
            'title' => 'test task',
            'mode' => 'lesson',
            'mediaType' => 'text',
            'content' => 'task content',
            'fromCourseSetId' => $courseSet['id'],
        ));

        $task2 = $this->getTaskService()->createTask(array(
            'fromCourseId' => $course['id'],
            'title' => 'test task',
            'mode' => 'lesson',
            'mediaType' => 'text',
            'content' => 'task content',
            'fromCourseSetId' => $courseSet['id'],
        ));

        $createdNote1 = $this->getNoteService()->saveNote(array(
            'content' => 'note_content1',
            'taskId' => $task1['id'],
            'courseId' => $task1['courseId'],
        ));

        $createdNote2 = $this->getNoteService()->saveNote(array(
            'content' => 'note_content1',
            'taskId' => $task2['id'],
            'courseId' => $task2['courseId'],
        ));

        $resultCount = $this->getNoteService()->countCourseNotes(array('courseId' => $task1['courseId'], 'taskId' => $task2['id']));
        $this->assertEquals(1, $resultCount);
        $resultCount = $this->getNoteService()->countCourseNotes(array('courseId' => $course['id']));
        $this->assertEquals(2, $resultCount);
    }

    public function testSearchNotes()
    {
        $user = $this->getCurrentUser();
        $courseSet = $this->createCourseSet();
        $course = $this->getCourseService()->getDefaultCourseByCourseSetId($courseSet['id']);

        $task1 = $this->getTaskService()->createTask(array(
            'fromCourseId' => $course['id'],
            'title' => 'test task',
            'mode' => 'lesson',
            'mediaType' => 'text',
            'content' => 'task content',
            'fromCourseSetId' => $courseSet['id'],
        ));

        $task2 = $this->getTaskService()->createTask(array(
            'fromCourseId' => $course['id'],
            'title' => 'test task',
            'mode' => 'lesson',
            'mediaType' => 'text',
            'content' => 'task content',
            'fromCourseSetId' => $courseSet['id'],
        ));

        $createdNote1 = $this->getNoteService()->saveNote(array(
            'content' => 'note_content1',
            'taskId' => $task1['id'],
            'courseId' => $task1['courseId'],
        ));

        $createdNote2 = $this->getNoteService()->saveNote(array(
            'content' => 'note_content1',
            'taskId' => $task2['id'],
            'courseId' => $task2['courseId'],
        ));

        $searchedNotes = $this->getNoteService()->searchNotes(
            array('courseId' => $course['id']),
            array('createdTime' => 'DESC'), 0, 30);
        $this->assertEquals(2, count($searchedNotes));
        $this->assertContains($createdNote2, $searchedNotes);
    }

    public function testSaveNote()
    {
        $note = $this->createNote();

        $savedNote = $this->getNoteService()->saveNote(array(
            'content' => 'Save Second Time',
            'taskId' => $note['taskId'],
            'courseId' => $note['courseId'],
        ));

        $this->assertNotNull($savedNote);
    }

    public function testDeleteNote()
    {
        $note = $this->createNote();
        $this->getNoteService()->deleteNote($note['id']);
        $note = $this->getNoteService()->getNote($note['id']);
        $this->assertNull($note);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testDeleteNoteWithNotExist()
    {
        $this->getNoteService()->deleteNote(999);
    }

    public function testDeleteNotes()
    {
        $user = $this->getCurrentUser();
        $courseSet = $this->createCourseSet();
        $course = $this->getCourseService()->getDefaultCourseByCourseSetId($courseSet['id']);

        $task1 = $this->getTaskService()->createTask(array(
            'fromCourseId' => $course['id'],
            'title' => 'test task',
            'mode' => 'lesson',
            'mediaType' => 'text',
            'content' => 'task content',
            'fromCourseSetId' => $courseSet['id'],
        ));

        $task2 = $this->getTaskService()->createTask(array(
            'fromCourseId' => $course['id'],
            'title' => 'test task',
            'mode' => 'lesson',
            'mediaType' => 'text',
            'content' => 'task content',
            'fromCourseSetId' => $courseSet['id'],
        ));

        $createdNote1 = $this->getNoteService()->saveNote(array(
            'content' => 'note_content1',
            'taskId' => $task1['id'],
            'courseId' => $task1['courseId'],
        ));

        $createdNote2 = $this->getNoteService()->saveNote(array(
            'content' => 'note_content1',
            'taskId' => $task2['id'],
            'courseId' => $task2['courseId'],
        ));

        $ids = array($createdNote1['id'], $createdNote2['id']);
        $result = $this->getNoteService()->deleteNotes($ids);

        $note1 = $this->getNoteService()->getNote($createdNote1['id']);
        $this->assertNull($note1);

        $note2 = $this->getNoteService()->getNote($createdNote1['id']);
        $this->assertNull($note2);
    }

    protected function createCourseSet()
    {
        $courseSet = $this->getCourseSetService()->createCourseSet(array(
            'title' => 'test set',
            'type' => 'normal',
        ));

        return $courseSet;
    }

    protected function createTask()
    {
        $courseSet = $this->createCourseSet();
        $course = $this->getCourseService()->getDefaultCourseByCourseSetId($courseSet['id']);
        $task = $this->getTaskService()->createTask(array(
            'fromCourseId' => $course['id'],
            'title' => 'test task',
            'mode' => 'lesson',
            'mediaType' => 'text',
            'content' => 'task content',
            'fromCourseSetId' => $courseSet['id'],
        ));

        return $task;
    }

    protected function createNote()
    {
        $task = $this->createTask();
        $note = $this->getNoteService()->saveNote(array(
            'content' => 'note content',
            'taskId' => $task['id'],
            'courseId' => $task['courseId'],
        ));

        return $note;
    }

    /**
     * @return CourseNoteService
     */
    protected function getNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }
}
