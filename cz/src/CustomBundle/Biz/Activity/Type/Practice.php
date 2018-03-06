<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class Practice extends Activity
{
    protected function registerListeners()
    {
        // TODO: Implement registerListeners() method.
    }

    /**
     * {@inheritdoc}
     */
    public function create($fields)
    {
        $biz = $this->getBiz();
        $files = array();
        if (!empty($fields['materials'])) {
            $files = json_decode($fields['materials'], true);
        }

        $fileIds = array_keys($files);

        $downloadActivity = array('mediaCount' => count($files), 'fileIds' => $fileIds);
        $downloadActivity['createdUserId'] = $biz['user']['id'];
        $downloadActivity = $this->getPracticeActivityDao()->create($downloadActivity);

        return $downloadActivity;
    }

    public function copy($activity, $config = array())
    {
        $download = $this->getPracticeActivityDao()->get($activity['mediaId']);
        $newDownload = array(
            'mediaCount' => $download['mediaCount'],
            'fileIds' => $download['fileIds'],
        );

        return $this->getPracticeActivityDao()->create($newDownload);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceDownload = $this->getPracticeActivityDao()->get($sourceActivity['mediaId']);
        $download = $this->getPracticeActivityDao()->get($activity['mediaId']);
        $download['mediaCount'] = $sourceDownload['mediaCount'];
        $download['fileIds'] = $sourceDownload['fileIds'];

        return $this->getPracticeActivityDao()->update($download['id'], $download);
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, &$fields, $activity)
    {
        $files = array();
        if (!empty($fields['materials'])) {
            $files = json_decode($fields['materials'], true);
        } else {
            $this->deleateMAterial($activity);
        }

        $fileIds = array_keys($files);

        $downloadActivity = array('mediaCount' => count($files), 'fileIds' => $fileIds);
        $downloadActivity = $this->getPracticeActivityDao()->update($id, $downloadActivity);

        return $downloadActivity;
    }

    public function deleateMAterial($activity)
    {
        $materials = $this->getMaterialService()->findMaterialsByLessonId($activity['id']);

        foreach ($materials as $material) {
            $this->getMaterialService()->deleteMaterial($activity['fromCourseSetId'], $material['id']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->getPracticeActivityDao()->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->getPracticeActivityDao()->get($id);
    }

    public function find($ids)
    {
        return $this->getPracticeActivityDao()->findByIds($ids);
    }

    /**
     * @return PracticeActivityDao
     */
    public function getPracticeActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:PracticeActivityDao');
    }

    public function materialSupported()
    {
        return true;
    }

    protected function getConnection()
    {
        return $this->getBiz()->offsetGet('db');
    }

    protected function getMaterialService()
    {
        return $this->getBiz()->service('CustomBundle:Course:MaterialService');
    }
}
