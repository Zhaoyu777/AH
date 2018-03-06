<?php

namespace Tests\Unit\CustomBundle\Practice;

use Biz\BaseTestCase;

class PracticeResultServiceTest extends BaseTestCase
{
    public function testCreateContent()
    {
        $fields = $this->mockContent();

        $content = $this->getPracticeResultService()->createContent($fields);

        $this->assertEquals($fields['resultId'], $content['resultId']);
        $this->assertEquals($fields['uri'], $content['uri']);
        $this->assertEquals($fields['userId'], $content['userId']);
        $this->assertEquals(0, $content['likeNum']);
        $this->assertEquals(0, $content['postNum']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateContentWhileLackOfRequiredField()
    {
        $fields = array(
            'resultId' => 1,
        );

        $content = $this->getPracticeResultService()->createContent($fields);
    }

    public function testCreateContentWithOutUserId()
    {
        $fields = array(
            'resultId' => 1,
            'uri' => 'private/files/practice/2017/12-20/unit-test.png',
        );

        $content = $this->getPracticeResultService()->createContent($fields);

        $this->assertEquals($fields['resultId'], $content['resultId']);
        $this->assertEquals($fields['uri'], $content['uri']);
        $this->assertEquals(0, $content['likeNum']);
        $this->assertEquals(0, $content['postNum']);
    }

    public function testGetContent()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $content = $this->getPracticeResultService()->getContent($created['id']);
        $this->assertEquals($created, $content);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testLikeWhileContentNotExist()
    {
        $fields = $this->mockContent();

        $content = $this->getPracticeResultService()->createContent($fields);
        $this->getPracticeResultService()->like($content['id'] + 1);
    }

    public function testLike()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $like = $this->getPracticeResultService()->like($created['id']);

        $this->assertEquals($like['contentId'], $created['id']);

        $content = $this->getPracticeResultService()->getContent($created['id']);

        $this->assertEquals($content['likeNum'], 1);
    }

    public function testLikeRepeat()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $this->getPracticeResultService()->like($created['id']);
        $this->getPracticeResultService()->like($created['id']);
        $this->getPracticeResultService()->like($created['id']);

        $content = $this->getPracticeResultService()->getContent($created['id']);

        $this->assertEquals($content['likeNum'], 1);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testCancelLikeWhileContentNotExist()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $this->getPracticeResultService()->like($created['id']);

        $this->getPracticeResultService()->cancelLike($created['id'] + 1);
    }

    public function testCancelLike()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $this->getPracticeResultService()->like($created['id']);

        $this->getPracticeResultService()->cancelLike($created['id']);
        $content = $this->getPracticeResultService()->getContent($created['id']);

        $this->assertEquals($content['likeNum'], 0);
    }

    public function testCancelLikeRepeat()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $this->getPracticeResultService()->like($created['id']);

        $this->getPracticeResultService()->cancelLike($created['id']);
        $this->getPracticeResultService()->cancelLike($created['id']);
        $this->getPracticeResultService()->cancelLike($created['id']);
        $content = $this->getPracticeResultService()->getContent($created['id']);

        $this->assertEquals($content['likeNum'], 0);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testIsLikeWhileNotExist()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $this->getPracticeResultService()->isLike($created['id'] + 1);
    }

    public function testIsLike()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);

        $isLike = $this->getPracticeResultService()->isLike($created['id']);
        $this->assertFalse($isLike);

        $this->getPracticeResultService()->like($created['id']);
        $isLike = $this->getPracticeResultService()->isLike($created['id']);
        $this->assertTrue($isLike);

        $this->getPracticeResultService()->cancelLike($created['id']);
        $isLike = $this->getPracticeResultService()->isLike($created['id']);
        $this->assertFalse($isLike);
    }

    public function testGetLikeByContentIdAndUserId()
    {
        $fields = $this->mockContent();

        $created = $this->getPracticeResultService()->createContent($fields);
        $affected = $this->getPracticeResultService()->like($created['id']);

        $user = $this->getCurrentUser();
        $like = $this->getPracticeResultService()->getLikeByContentIdAndUserId($created['id'], $user['id']);

        $this->assertEquals($affected, $like);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreatePostWhileLackOfRequiredFields()
    {
        $fields = array(
            'content' => '单侧测试测试评论',
        );

        $this->getPracticeResultService()->createPost($fields);
    }

    public function testCreatePost()
    {
        $content = $this->mockContent();

        $content = $this->getPracticeResultService()->createContent($content);
        $fields = array(
            'contentId' => $content['id'],
            'content' => '单侧测试测试评论',
        );

        $post = $this->getPracticeResultService()->createPost($fields);

        $this->assertEquals($post['contentId'], $fields['contentId']);
        $this->assertEquals($post['content'], $fields['content']);
        $content = $this->getPracticeResultService()->getContent($content['id']);

        $this->assertEquals($content['postNum'], 1);
    }

    public function testGetPost()
    {
        $content = $this->mockContent();

        $content = $this->getPracticeResultService()->createContent($content);
        $fields = array(
            'contentId' => $content['id'],
            'content' => '单侧测试测试评论',
        );

        $created = $this->getPracticeResultService()->createPost($fields);

        $post = $this->getPracticeResultService()->getPost($created['id']);

        $this->assertEquals($created, $post);
    }

    public function testDeletePost()
    {
        $content = $this->mockContent();

        $content = $this->getPracticeResultService()->createContent($content);
        $fields = array(
            'contentId' => $content['id'],
            'content' => '单侧测试测试评论',
        );

        $post = $this->getPracticeResultService()->createPost($fields);

        $content = $this->getPracticeResultService()->getContent($content['id']);
        $this->assertEquals($content['postNum'], 1);

        $this->getPracticeResultService()->deletePost($post['id']);
        $content = $this->getPracticeResultService()->getContent($content['id']);
        $this->assertEquals($content['postNum'], 0);
    }

    public function testDeletePostRepeat()
    {
        $content = $this->mockContent();

        $content = $this->getPracticeResultService()->createContent($content);
        $fields = array(
            'contentId' => $content['id'],
            'content' => '单侧测试测试评论',
        );

        $post = $this->getPracticeResultService()->createPost($fields);

        $content = $this->getPracticeResultService()->getContent($content['id']);
        $this->assertEquals($content['postNum'], 1);

        $this->getPracticeResultService()->deletePost($post['id']);
        $content = $this->getPracticeResultService()->getContent($content['id']);
        $this->assertEquals($content['postNum'], 0);

        $this->getPracticeResultService()->deletePost($post['id']);
        $content = $this->getPracticeResultService()->getContent($content['id']);
        $this->assertEquals($content['postNum'], 0);
    }

    public function mockContent($fields = array())
    {
        $default = array(
            'resultId' => 1,
            'uri' => 'private/files/practice/2017/12-20/unit-test.png',
            'userId' => 1,
        );

        return array_merge($default, $fields);
    }

    protected function getPracticeResultService()
    {
        return $this->createService('CustomBundle:Practice:PracticeResultService');
    }
}
