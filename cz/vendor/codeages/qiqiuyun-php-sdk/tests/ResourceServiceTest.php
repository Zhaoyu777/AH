<?php

namespace QiQiuYun\SDK\Tests;

use PHPUnit\Framework\TestCase;
use QiQiuYun\SDK\Service\ResourceService;
use QiQiuYun\SDK;

class ResourceServiceTest extends TestCase
{
    public function testGeneratePlayToken()
    {
        $secretKey = 'this_is_a_secret_key';
        $resNo = 'this_is_a_test_resource_no_1';
        $lifetime = 600;
        $deadline = time() + $lifetime;

        $token = $this->createResourceService(array(
            'secret_key' => $secretKey,
        ))->generatePlayToken($resNo, $lifetime);

        $parsedToken = explode(':', $token);

        $this->assertCount(3, $parsedToken);
        $this->assertEquals(16, strlen($parsedToken[0]));
        $this->assertEquals($deadline, $parsedToken[1]);

        $signingText = "{$resNo}\n{$parsedToken[0]}\n{$deadline}";
        $sign = hash_hmac('sha1', $signingText, $secretKey, true);
        $encodedSign = SDK\base64_urlsafe_encode($sign);

        $this->assertEquals($encodedSign, $parsedToken[2]);
    }

    /**
     * @return ResourceService
     */
    protected function createResourceService(array $config = array())
    {
        $config = array_merge(array(
            'access_key' => 'test_access_key',
            'secret_key' => 'test_secret_key',
        ), $config);

        return new ResourceService($config);
    }
}
