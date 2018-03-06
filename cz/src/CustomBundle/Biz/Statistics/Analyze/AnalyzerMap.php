<?php

namespace CustomBundle\Biz\Statistics\Analyze;

class AnalyzerMap
{
    public static function getMetaInfos()
    {
        return array(
            'studentCourse' => array(
                'class' => 'CustomBundle\Biz\Statistics\Analyze\Analyzer\StudentCourseAnalyzer',
            ),
        );
    }
}
