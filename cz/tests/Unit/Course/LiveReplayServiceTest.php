<?php

namespace Tests\Unit\Course;

use Mockery;
use Biz\BaseTestCase;

class LiveReplayServiceTest extends BaseTestCase
{
    public function testGetReplay()
    {
        $replay = $this->createLiveReplay();

        $result = $this->getLiveReplayService()->getReplay($replay['id']);

        $this->assertEquals($replay['replayId'], $result['replayId']);
        $this->assertEquals($replay['globalId'], $result['globalId']);
        $this->assertEquals($replay['title'], $result['title']);
    }

    public function testFindReplayByLessonId()
    {
        $replay1 = $this->createLiveReplay();
        $replay2 = $this->createLiveReplay1();

        $replays = $this->getLiveReplayService()->findReplayByLessonId(1, 'liveOpen');

        $this->assertEquals(1, count($replays));
        $this->assertEquals($replay1['title'], $replays[0]['title']);
        $this->assertEquals($replay1['globalId'], $replays[0]['globalId']);
    }

    public function testAddReplay()
    {
        $fields = array(
            'courseId' => 1,
            'lessonId' => 1,
            'title' => 'live replay',
            'replayId' => 1,
            'globalId' => '5aac664d11ca403a853749sssss12345',
            'type' => 'liveOpen',
        );

        $replay = $this->getLiveReplayService()->addReplay($fields);

        $this->assertEquals($fields['replayId'], $replay['replayId']);
        $this->assertEquals($fields['globalId'], $replay['globalId']);
        $this->assertEquals($fields['title'], $replay['title']);
    }

    public function testDeleteReplayByLessonId()
    {
        $replay1 = $this->createLiveReplay();
        $replay2 = $this->createLiveReplay1();

        $this->getLiveReplayService()->deleteReplayByLessonId(1, 'live');

        $result = $this->getLiveReplayService()->getReplay($replay2['id']);
        $result1 = $this->getLiveReplayService()->getReplay($replay1['id']);

        $this->assertNull($result);
        $this->assertEquals($replay1['replayId'], $result1['replayId']);
        $this->assertEquals($replay1['globalId'], $result1['globalId']);
        $this->assertEquals($replay1['title'], $result1['title']);
    }

    public function testDeleteReplaysByCourseId()
    {
        $replay1 = $this->createLiveReplay();
        $replay2 = $this->createLiveReplay1();
        $replay3 = $this->createLiveReplay2();

        $this->getLiveReplayService()->deleteReplaysByCourseId(1, 'live');

        $result1 = $this->getLiveReplayService()->getReplay($replay1['id']);
        $result2 = $this->getLiveReplayService()->getReplay($replay2['id']);
        $result3 = $this->getLiveReplayService()->getReplay($replay3['id']);

        $this->assertNull($result2);
        $this->assertNull($result3);

        $this->assertEquals($replay1['replayId'], $result1['replayId']);
        $this->assertEquals($replay1['globalId'], $result1['globalId']);
        $this->assertEquals($replay1['title'], $result1['title']);
    }

    public function testUpdateReplay()
    {
        $replay = $this->createLiveReplay();

        $fields = array(
            'title' => 'live replay update',
            'hidden' => 1,
        );
        $result = $this->getLiveReplayService()->updateReplay($replay['id'], $fields);

        $this->assertEquals($fields['title'], $result['title']);
        $this->assertEquals($fields['hidden'], $result['hidden']);
    }

    public function testUpdateReplayByLessonId()
    {
        $replay1 = $this->createLiveReplay();
        $replay2 = $this->createLiveReplay1();
        $replay3 = $this->createLiveReplay2();

        $fields = array(
            'hidden' => 1,
        );

        $this->getLiveReplayService()->updateReplayByLessonId(1, $fields, 'live');

        $result1 = $this->getLiveReplayService()->getReplay($replay1['id']);
        $result2 = $this->getLiveReplayService()->getReplay($replay2['id']);
        $result3 = $this->getLiveReplayService()->getReplay($replay3['id']);

        $this->assertEquals('0', $result1['hidden']);
        $this->assertEquals($fields['hidden'], $result2['hidden']);
        $this->assertEquals('0', $result3['hidden']);
    }

    public function testSearchCount()
    {
        $replay1 = $this->createLiveReplay();
        $replay2 = $this->createLiveReplay1();
        $replay3 = $this->createLiveReplay2();

        $conditions = array(
            'type' => 'live',
        );
        $count = $this->getLiveReplayService()->searchCount($conditions);

        $this->assertEquals(2, $count);
    }

    public function testSearchReplays()
    {
        $replay1 = $this->createLiveReplay();
        $replay2 = $this->createLiveReplay1();
        $replay3 = $this->createLiveReplay2();

        $conditions = array(
            'type' => 'live',
        );

        $results = $this->getLiveReplayService()->searchReplays($conditions, array('replayId' => 'ASC'), 0, 10);

        $this->assertEquals(2, count($results));
    }

    public function testFindReplaysByCourseIdAndLessonId()
    {
        $replay1 = $this->createLiveReplay();
        $replay2 = $this->createLiveReplay1();

        $replays = $this->getLiveReplayService()->findReplaysByCourseIdAndLessonId(1, 1, 'liveOpen');

        $this->assertEquals(1, count($replays));
        $this->assertEquals($replay1['title'], $replays[0]['title']);
        $this->assertEquals($replay1['globalId'], $replays[0]['globalId']);
    }

    protected function createLiveReplay()
    {
        $fields = array(
            'courseId' => 1,
            'lessonId' => 1,
            'title' => 'live replay',
            'replayId' => 1,
            'globalId' => '5aac664d11ca403a853749sssss12345',
            'type' => 'liveOpen',
        );

        return $this->getLiveReplayService()->addReplay($fields);
    }

    protected function createLiveReplay1()
    {
        $fields = array(
            'courseId' => 1,
            'lessonId' => 1,
            'title' => 'live replay2',
            'replayId' => 4,
            'globalId' => 0,
            'type' => 'live',
        );

        return $this->getLiveReplayService()->addReplay($fields);
    }

    protected function createLiveReplay2()
    {
        $fields = array(
            'courseId' => 1,
            'lessonId' => 2,
            'title' => 'live replay3',
            'replayId' => 5,
            'globalId' => 0,
            'type' => 'live',
        );

        return $this->getLiveReplayService()->addReplay($fields);
    }

    protected function createApiMock($no)
    {
        $api = CloudAPIFactory::create('root');
        $mockObject = Mockery::mock($api);
        $mockObject->shouldReceive('post')->times(1)->andReturn(array('no' => $no));
        $this->getLiveReplayService()->createLiveClient($mockObject);
    }

    protected function getLiveReplayService()
    {
        return $this->createService('Course:LiveReplayService');
    }
}
