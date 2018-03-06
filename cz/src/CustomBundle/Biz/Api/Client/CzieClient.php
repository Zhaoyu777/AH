<?php

namespace CustomBundle\Biz\Api\Client;

class CzieClient extends AbstractAPI
{
    private static $_instance;
    const APP_ID  = '58171047371434360415';
    const APP_KEY = 'e885c1736feaa486eeb9aac4a6cd73a1';

    public static function instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function getTerms()
    {
        $body   = $this->get('http://172.18.0.32/android_cx/ios_cx_json.asmx/SearchAllXq');
        $result = $this->converter($body);
        $result = $result[0]['mes'];
        return $result;
    }

    public function getCurrentTerm()
    {
        return '17-18-2';
        $body   = $this->get('http://172.18.0.32/android_cx/ios_cx_json.asmx/Search_Xq_Now');
        $result = $this->converter($body);
        $result = $result[0]['mess'][0]['xq'];
        return $result;
    }

    public function getCourseSet()
    {
        $body   = $this->get('http://172.18.0.32/android_cx/ios_cx_json_1.asmx/Search_kcdm');
        $result = $this->converter($body);
        $result = $result[0]['mess'];
        return $result;
    }

    public function getAccessToken()
    {
        $params = array(
            'appid'  => self::APP_ID,
            'appkey' => self::APP_KEY
        );
        $body   = $this->get('http://172.18.0.64:8082/ly_web_services/validation', $params);
        $result = json_decode($body, true);
        return $result['access_token'];
    }

    public function getStudents()
    {
        $params = array(
            'appid'        => self::APP_ID,
            'access_token' => $this->getAccessToken()
        );
        $body   = $this->get('http://172.18.0.64:8082/ly_web_services/web/xsjbxx', $params);
        $result = json_decode($body, true);

        if ($result['code'] == 0) {
            return $result['data'];
        }

        throw new \Exception("get students info is error: {$result['message']}.");
    }

    public function getTeacherOrgs()
    {
        $params = array(
            'appid'        => self::APP_ID,
            'access_token' => $this->getAccessToken()
        );
        $body   = $this->get('http://172.18.0.64:8082/ly_web_services/web/zzjg', $params);
        $result = json_decode($body, true);

        if ($result['code'] == 0) {
            return $result['data'];
        }

        throw new \Exception("get teacher orgs is error: {$result['message']}.");
    }

    public function getTeachers()
    {
        $params = array(
            'appid'        => self::APP_ID,
            'access_token' => $this->getAccessToken()
        );
        $body   = $this->get('http://172.18.0.64:8082/ly_web_services/web/jsjbxx', $params);
        $result = json_decode($body, true);

        if ($result['code'] == 0) {
            return $result['data'];
        }

        throw new \Exception("get teacher orgs is error: {$result['message']}.");
    }

    public function getCoursesByTerm($trem)
    {
        $params = array(
            'xq' => $trem
        );
        $body   = $this->get('http://172.18.0.32/android_cx/ios_cx_json_1.asmx/Search_Kbk_Xq', $params);
        $result = $this->converter($body);
        $result = $result[0]['mess'];
        return $result;
    }

    public function getCourseMembers($trem, $courseCode, $classroomNos, $type, $category)
    {
        $params = array(
            'xq'   => $trem,
            'kch'  => $courseCode,
            'hb'   => $classroomNos,
            'lx'   => $type,
            'lbdm' => $category
        );
        $body   = $this->get('http://172.18.0.32/android_cx/ios_cx_json.asmx/SearchKcXqXkmd', $params);
        $result = $this->converter($body);
        $result = $result[0]['mess'];
        return $result;
    }

    private function converter($body)
    {
        $body   = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $body);
        $body   = str_replace('<string xmlns="http://tempuri.org/">', '', $body);
        $body   = str_replace('</string>', '', $body);
        $result = json_decode($body, true);
        return $result;
    }
}
