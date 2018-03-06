<?php

namespace CustomBundle\Twig;

use Codeages\Biz\Framework\Context\Biz;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TermExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var Biz
     */
    protected $biz;

    protected $pageScripts;

    public function __construct($container, Biz $biz)
    {
        $this->container = $container;
        $this->biz = $biz;
    }

    public function getFilters()
    {
        return array();
    }

    public function getFunctions()
    {
        $options = array('is_safe' => array('html'));

        return array(
            new \Twig_SimpleFunction('term_choices', array($this, 'getTermChoices'), $options),
            new \Twig_SimpleFunction('checked_term_choices', array($this, 'getCheckedTermChoices'), $options),
        );
    }

    public function getCheckedTermChoices($checked = "")
    {
        $terms = $this->getCourseService()->findTerms();

        $html = '';
        $html .= "<option value=\"\"";
        if (empty($checked)) {
            $html .= "selected=\"selected\"";
        }
        $html .= "<option value=\"\">全部</option>";

        foreach ($terms as $key => $term) {
            if ($term['shortCode'] == $checked) {
                $html .= "<option value=\"{$term['shortCode']}\" selected=\"selected\">{$term['title']}</option>";
            } else {
                $html .= "<option value=\"{$term['shortCode']}\">{$term['title']}</option>";
            }
        }

        return $html;
    }

    public function getTermChoices()
    {
        $terms = $this->getCourseService()->findTerms();

        $html = '';
        foreach ($terms as $key => $term) {
            if ($term['current'] == true) {
                $html .= "<option value=\"{$term['shortCode']}\" selected=\"selected\">{$term['title']}</option>";
            } else {
                $html .= "<option value=\"{$term['shortCode']}\">{$term['title']}</option>";
            }
        }

        return $html;
    }

    public function getName()
    {
        return 'custom_term_twig';
    }

    protected function getCourseService()
    {
        return $this->biz->service('CustomBundle:Course:CourseService');
    }
}