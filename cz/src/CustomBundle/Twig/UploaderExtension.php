<?php

namespace CustomBundle\Twig;

use AppBundle\Util\UploaderToken;

class UploaderExtension extends \Twig_Extension
{
    protected $container;

    protected $pageScripts;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array();
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('custom_uploader_accept', array($this, 'getUploadFileAccept')),
        );
    }

    public function getUploadFileAccept($targetType, $only = '')
    {
        $targetAcceptTypes = array(
            'homework' => array('doc', 'xls', 'ppt', 'zip', 'pdf', 'image'),
        );
        $availableAccepts = array(
            'ppt' => array(
                'extensions' => array('ppt', 'pptx'),
                'mimeTypes' => array('application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
            ),
            'doc' => array(
                'extensions' => array('doc', 'docx'),
                'mimeTypes' => array('application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ),
            'xls' => array(
                'extensions' => array('xls', 'xlsx'),
                'mimeTypes' => array('application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ),
            'pdf' => array(
                'extensions' => array('pdf'),
                'mimeTypes' => array('application/pdf'),
            ),
            'zip' => array(
                'extensions' => array('zip', 'rar', 'gz', 'tar', '7z'),
                'mimeTypes' => array('application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed', 'application/x-tar', 'application/x-gzip', 'application/x-7zip'),
            ),
            'image' => array(
                'extensions' => array('jpg', 'jpeg', 'png'),
                'mimeTypes' => array('image/jpg,image/jpeg,image/png'),
            ),
        );

        $types = array();

        $only = explode(',', $only);

        if ($only && !empty($only[0])) {
            $types = $only;
        } elseif (isset($targetAcceptTypes[$targetType])) {
            $types = $targetAcceptTypes[$targetType];
        } else {
            $types = array('all');
        }

        $accept = array('extensions' => array(), 'mimeTypes' => array());

        foreach ($types as $type) {
            if (isset($availableAccepts[$type])) {
                $accept['extensions'] = array_merge($accept['extensions'], $availableAccepts[$type]['extensions']);
                $accept['mimeTypes'] = array_merge($accept['mimeTypes'], $availableAccepts[$type]['mimeTypes']);
            }
        }

        return $accept;
    }

    public function getName()
    {
        return 'custom_uploader_twig';
    }
}