<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\BaseDataTag;
use AppBundle\Extensions\DataTag\DataTag;

class ActivityConnAimsDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['activityId'])) {
            return;
        }

        $relations = $this->getTeachingAimActivityService()->findByActivityId($arguments['activityId']);
        $typeAims = $this->getTeachingAimActivityService()->processRelations($relations);

        $results = array();
        foreach ($typeAims as $aims) {
            foreach($aims as $aim) {
                if ($aim['type'] == 'abilityAim') {
                    $type = '能力目标';
                }
    
                if ($aim['type'] == 'knowledgeAim') {
                    $type = '知识目标';   
                }
    
                if ($aim['type'] == 'qualityAim') {
                    $type = '素质目标';
                }

                $results[] = array(
                    'id' => $aim['id'],
                    'type' => $type,
                    'content' => $aim['content'],
                );
            }
        }

        return $results;
    }

    protected function getTeachingAimActivityService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Lesson:TeachingAimActivityService');
    }
}
