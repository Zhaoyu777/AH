<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class Questionnaire extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getQuestionnaireActivityDao()->get($targetId);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceQuestionnaire = $this->getQuestionnaireActivityDao()->get($sourceActivity['mediaId']);
        $questionnaire = $this->getQuestionnaireActivityDao()->get($activity['mediaId']);
        $questionnaire['duration'] = $sourceQuestionnaire['duration'];

        return $this->getQuestionnaireActivityDao()->update($questionnaire['id'], $questionnaire);
    }

    public function update($targetId, &$fields, $activity)
    {
        $biz = $this->getBiz();
        $questionnaire['mediaId'] = $fields['mediaId'];
        $this->getCourseDraftService()->deleteCourseDrafts($activity['fromCourseId'], $activity['id'], $biz['user']['id']);

        return $this->getQuestionnaireActivityDao()->update($targetId, $questionnaire);
    }

    public function delete($targetId)
    {
        return $this->getQuestionnaireActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $biz = $this->getBiz();
        $questionnaire['mediaId'] = $fields['mediaId'];

        return $this->getQuestionnaireActivityDao()->create($questionnaire);
    }

    public function find($targetIds)
    {
        return $this->getQuestionnaireActivityDao()->findByIds($targetIds);
    }

    protected function getQuestionnaireActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:QuestionnaireActivityDao');
    }

    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('Activity:ActivityLearnLogService');
    }

    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    protected function getCourseDraftService()
    {
        return $this->getBiz()->service('Course:CourseDraftService');
    }
}
