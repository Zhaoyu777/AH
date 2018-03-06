<?php

namespace CustomBundle\Extension;

use Biz\Question\Type\Choice;
use Biz\Question\Type\Determine;
use Biz\Question\Type\Essay;
use Biz\Question\Type\Fill;
use Biz\Question\Type\Material;
use Biz\Question\Type\SingleChoice;
use Biz\Question\Type\UncertainChoice;
use Biz\Testpaper\Pattern\QuestionTypePattern;
use Pimple\Container;
use AppBundle\Extension\Extension;
use Pimple\ServiceProviderInterface;

class QuestionExtension extends Extension implements ServiceProviderInterface
{
    public function getQuestionTypes()
    {
        return array(
            'single_choice' => array(
                'name' => '单选题',
                'actions' => array(
                    'create' => 'AppBundle:Question/SingleChoiceQuestion:create',
                    'edit' => 'AppBundle:Question/SingleChoiceQuestion:edit',
                    'show' => 'AppBundle:Question/SingleChoiceQuestion:show',
                ),
                'templates' => array(
                    'do' => 'question/single-choice-do.html.twig',
                    'statis' => 'question/statis/single-choice.html.twig',
                    'analysis' => 'question/analysis/single-choice.html.twig',
                ),
                'hasMissScore' => 0,
            ),
            'choice' => array(
                'name' => '多选题',
                'actions' => array(
                    'create' => 'AppBundle:Question/ChoiceQuestion:create',
                    'edit' => 'AppBundle:Question/ChoiceQuestion:edit',
                    'show' => 'AppBundle:Question/ChoiceQuestion:show',
                ),
                'templates' => array(
                    'do' => 'question/choice-do.html.twig',
                    'statis' => 'question/statis/choice.html.twig',
                    'analysis' => 'question/analysis/choice.html.twig',
                ),
                'hasMissScore' => 1,
            ),
            'essay' => array(
                'name' => '问答题',
                'actions' => array(
                    'create' => 'AppBundle:Question/EssayQuestion:create',
                    'edit' => 'AppBundle:Question/EssayQuestion:edit',
                    'show' => 'AppBundle:Question/EssayQuestion:show',
                ),
                'templates' => array(
                    'do' => 'question/essay-do.html.twig',
                    'statis' => 'question/statis/essay.html.twig',
                ),
                'hasMissScore' => 0,
            ),
            'uncertain_choice' => array(
                'name' => '不定项选择题',
                'actions' => array(
                    'create' => 'AppBundle:Question/UncertainChoiceQuesiton:create',
                    'edit' => 'AppBundle:Question/UncertainChoiceQuesiton:edit',
                    'show' => 'AppBundle:Question/UncertainChoiceQuesiton:show',
                ),
                'templates' => array(
                    'do' => 'question/uncertain-choice-do.html.twig',
                    'statis' => 'question/statis/uncertain-choice.html.twig',
                    'analysis' => 'question/analysis/uncertain-choice.html.twig',
                ),
                'hasMissScore' => 1,
            ),
            'determine' => array(
                'name' => '判断题',
                'actions' => array(
                    'create' => 'AppBundle:Question/DetermineQuestion:create',
                    'edit' => 'AppBundle:Question/DetermineQuestion:edit',
                    'show' => 'AppBundle:Question/DetermineQuestion:show',
                ),
                'templates' => array(
                    'do' => 'question/determine-do.html.twig',
                    'statis' => 'question/statis/determine.html.twig',
                    'analysis' => 'question/analysis/determine.html.twig',
                ),
                'hasMissScore' => 0,
            ),
            'fill' => array(
                'name' => '填空题',
                'actions' => array(
                    'create' => 'AppBundle:Question/FillQuestion:create',
                    'edit' => 'AppBundle:Question/FillQuestion:edit',
                    'show' => 'AppBundle:Question/FillQuestion:show',
                ),
                'templates' => array(
                    'do' => 'question/fill-do.html.twig',
                    'statis' => 'question/statis/fill.html.twig',
                    'analysis' => 'question/analysis/fill.html.twig',
                ),
                'hasMissScore' => 0,
            ),
            'material' => array(
                'name' => '材料题',
                'actions' => array(
                    'create' => 'AppBundle:Question/MaterialQuestion:create',
                    'edit' => 'AppBundle:Question/MaterialQuestion:edit',
                    'show' => 'AppBundle:Question/MaterialQuestion:show',
                ),
                'templates' => array(
                    'do' => 'question/material-do.html.twig',
                    'statis' => 'question/statis/material.html.twig',
                ),
                'hasMissScore' => 0,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $container)
    {
        $container['question_type.choice'] = function ($biz) {
            $obj = new Choice();
            $obj->setBiz($biz);

            return $obj;
        };
        $container['question_type.single_choice'] = function ($biz) {
            $obj = new SingleChoice();
            $obj->setBiz($biz);

            return $obj;
        };
        $container['question_type.uncertain_choice'] = function ($biz) {
            $obj = new UncertainChoice();
            $obj->setBiz($biz);

            return $obj;
        };
        $container['question_type.determine'] = function ($biz) {
            $obj = new Determine();
            $obj->setBiz($biz);

            return $obj;
        };
        $container['question_type.essay'] = function ($biz) {
            $obj = new Essay();
            $obj->setBiz($biz);

            return $obj;
        };
        $container['question_type.fill'] = function ($biz) {
            $obj = new Fill();
            $obj->setBiz($biz);

            return $obj;
        };
        $container['question_type.material'] = function ($biz) {
            $obj = new Material();
            $obj->setBiz($biz);

            return $obj;
        };

        $container['testpaper_pattern.questionType'] = function ($container) {
            return new QuestionTypePattern($container);
        };
    }
}
