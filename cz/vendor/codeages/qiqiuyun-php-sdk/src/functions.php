<?php

namespace {
    if (!function_exists('json_last_error_msg')) {
        function json_last_error_msg()
        {
            static $JSON_ERRORS = array(
                JSON_ERROR_NONE => 'No error',
                JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
                JSON_ERROR_SYNTAX => 'Syntax error',
                JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            );

            $error = json_last_error();

            return isset($JSON_ERRORS[$error]) ? $JSON_ERRORS[$error] : 'Unknown error';
        }
    }
}

namespace QiQiuYun\SDK {
    function base64_urlsafe_encode($data)
    {
        $find = array('+', '/');
        $replace = array('-', '_');

        return str_replace($find, $replace, base64_encode($data));
    }

    function base64_urlsafe_decode($str)
    {
        $find = array('-', '_');
        $replace = array('+', '/');

        return base64_decode(str_replace($find, $replace, $str));
    }

    function json_decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        $data = \json_decode($json, $assoc, $depth, $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                'json_decode error: '.json_last_error_msg());
        }

        return $data;
    }

    function random_str($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }
}
