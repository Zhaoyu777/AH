<?php

namespace CustomBundle\Biz\Course\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use CustomBundle\Biz\Statistics\Analyze\DatasAnalyze;

class AnalyzeCourseStudentsStatisticsJob extends AbstractJob
{
    public function execute()
    {
        $datasAnalyze = new DatasAnalyze($this->biz);

        $datasAnalyze->analyze();
    }
}