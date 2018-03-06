<?php

namespace CustomBundle\Controller\CourseStatistics;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class SchoolStatisticsController extends BaseController
{
    public function indexAction(Request $request)
    {
        list(
            $teachersPrepareLessonsCount,
            $teachersReportsCount,
            $teachersStartCoursesCount,
            $schoolAttendsRate,
            $homeworkTaskSettingRate,
            $todayPv,
            $todayUv,
            $attendantRateWarning,
            $teachTargetWarning,
            $activityJoinWarning
        ) = $this->buildSchooleInfos();

        $collegesOrgs = $this->buildCollegesOrgs();
        $collegsInfos = array();
        foreach ($collegesOrgs as $org) {
            $startCourseCount = $this->getPrepareCourseLogService()->countCurrentTermLogByOrgCode($org['orgCode']);
            $collegsInfos[] = array(
                'id' => $org['id'],
                'name' => $org['name'],
                'startCourseCount' => $startCourseCount
            );
        }

        return $this->render('CustomBundle:databoard:index.html.twig', array(
            'collegsInfos'                => $collegsInfos,
            'teachersPrepareLessonsCount' => $teachersPrepareLessonsCount,
            'teachersReportsCount'        => $teachersReportsCount,
            'teachersStartCoursesCount'   => $teachersStartCoursesCount,
            'schoolAttendsRate'           => $schoolAttendsRate,
            'homeworkTaskSettingRate'     => $homeworkTaskSettingRate,
            'todayPv'                     => $todayPv,
            'todayUv'                     => $todayUv,
            'attendantRateWarning'        => $attendantRateWarning,
            'teachTargetWarning'          => $teachTargetWarning,
            'activityJoinWarning'         => $activityJoinWarning
        ));
    }

    protected function buildSchooleInfos()
    {
        $currentTermCode = $this->getCourseService()->getCurrentTerm();

        $teachersPrepareLessonsCount = $this->getCourseLessonService()->countSchoolTeachersPrepareLessonsByTermCode($currentTermCode['shortCode']);
        $teachersReportsCount = $this->getCourseLessonService()->countScholeTeacherReportsByTermCode($currentTermCode['shortCode']);
        $teachersStartCoursesCount = $this->getPrepareCourseLogService()->countCurrentTermLogByOrgCode('1.');
        $SchoolAttendsRate = $this->getTeacherCourseStatisticsService()->getAvgAttendRateByAllCourse();
        $homeworkTaskSettingRate = round(($this->getTaskService()->countByTaskType('homework') / $teachersPrepareLessonsCount) * 100, 1);

        // TODO
        // 活跃度
        // 预警信息

        return array(
            $teachersPrepareLessonsCount,
            $teachersReportsCount,
            $teachersStartCoursesCount,
            $SchoolAttendsRate,
            $homeworkTaskSettingRate,
            11305,
            1284,
            27,
            9,
            18
        );
    }

    public function collegesInfosAction(Request $request)
    {
        $collegesOrgs = $this->buildCollegesOrgs();

        $collegsInfos = array();
        foreach ($collegesOrgs as $org) {
            $startCourseCount = $this->getPrepareCourseLogService()->countCurrentTermLogByOrgCode($org['orgCode']);

            if ($org['name'] == '社科部') {
                $platformUseRate = '3.3';
                $teachTargetFinishRate = '83.9';
            }

            if ($org['name'] == '体育部') {
                $platformUseRate = '1.6';
                $teachTargetFinishRate = '75.3';
            }

            if ($org['name'] == '实训部') {
                $platformUseRate = '1.2';
                $teachTargetFinishRate = '73.2';
            }

            if ($org['name'] == '化工学院') {
                $platformUseRate = '9.2';
                $teachTargetFinishRate = '88.3';
            }

            if ($org['name'] == '制药学院') {
                $platformUseRate = '10.4';
                $teachTargetFinishRate = '84.1';
            }

            if ($org['name'] == '建工学院') {
                $platformUseRate = '12.5';
                $teachTargetFinishRate = '90.6';
            }

            if ($org['name'] == '装饰学院') {
                $platformUseRate = '9.8';
                $teachTargetFinishRate = '69.9';
            }

            if ($org['name'] == '信息学院') {
                $platformUseRate = '21.1';
                $teachTargetFinishRate = '93.8';
            }

            if ($org['name'] == '机电学院') {
                $platformUseRate = '15.5';
                $teachTargetFinishRate = '82.1';
            }

            if ($org['name'] == '经管学院') {
                $platformUseRate = '10.3';
                $teachTargetFinishRate = '84.4';
            }

            if ($org['name'] == '基础部') {
                $platformUseRate = '5.1';
                $teachTargetFinishRate = '86.3';
            }

            $collegsInfos[] = array(
                'id' => $org['id'],
                'name' => $org['name'],
                'startCourseCount' => $startCourseCount,
                'platformUseRate' => $platformUseRate,
                'teachTargetFinishRate' => $teachTargetFinishRate
            );
        }

        return $this->createJsonResponse($collegsInfos);
    }

    public function coursesInfosAction(Request $request)
    {
        $data = array(
            0 => array(
                'termName' => '15-16学年',
                'resourcesIncreaseRate' => '7.4',
                'satisfactionTrend' => '85.6'
            ),
            1 => array(
                'termName' => '16-17学年',
                'resourcesIncreaseRate' => '9.5',
                'satisfactionTrend' => '86.7'
            ),
            2 => array(
                'termName' => '17-18学年',
                'resourcesIncreaseRate' => '24.8',
                'satisfactionTrend' => '89.3'
            )
        );

        return $this->createJsonResponse($data);
    }

    public function collegeDetailAction(Request $request)
    {
        $orgCode = $request->query->get('orgCode');
        if ($orgCode == '1.') {
            return $this->renderAllCollegesPage($request, $orgCode);
        } else {
            return $this->renderCollegePage($request, $orgCode);
        }
    }

    protected function renderAllCollegesPage($request, $orgCode)
    {
        // 一周学院出勤预警排行
        $weekAttendWarningRanks = array(
            0 => array(
                'collegeName' => '建工学院',
                'times'       => '2'
            ),
            1 => array(
                'collegeName' => '制药学院',
                'times'       => '1'
            ),
            2 => array(
                'collegeName' => '信息学院',
                'times'       => '1'
            ),
            3 => array(
                'collegeName' => '化工学院',
                'times'       => '1'
            ),
            4 => array(
                'collegeName' => '装饰学院',
                'times'       => '0'
            ),
            5 => array(
                'collegeName' => '机电学院',
                'times'       => '0'
            ),
            6 => array(
                'collegeName' => '经管学院',
                'times'       => '0'
            ),
        );

        // 排行榜
        $rankings = array(
            'platformUses' => array(
                0 => array(
                    'collegeName' => '化工学院',
                    'data'        => '92.4',
                ),
                1 => array(
                    'collegeName' => '机电学院',
                    'data'        => '90.7',
                ),
                2 => array(
                    'collegeName' => '建工学院',
                    'data'        => '89.3',
                ),
            ),
            'activityJoinRates' => array(
                0 => array(
                    'activityName' => '化学基础及实验技术',
                    'data'         => '88.4'
                ),
                1 => array(
                    'activityName' => '现代生物技术',
                    'data'         => '86.3'
                ),
                2 => array(
                    'activityName' => '计算机应用基础',
                    'data'         => '84.7'
                ),
            ),
            'resourcesCounts' => array(
                0 => array(
                    'collegeName' => '信息学院',
                    'data'        => '1340'
                ),
                1 => array(
                    'collegeName' => '建工学院',
                    'data'        => '1305'
                ),
                2 => array(
                    'collegeName' => '机电学院',
                    'data'        => '1146'
                ),
            ),
            'attendRates' => array(
                0 => array(
                    'collegeName' => '经管学院',
                    'data'        => '95.2'
                ),
                1 => array(
                    'collegeName' => '机电学院',
                    'data'        => '92.8'
                ),
                2 => array(
                    'collegeName' => '装饰学院',
                    'data'        => '90.8'
                ),
            )
        );
        // 出勤
        $studentAttendWarnings = array(
            0 => array(
                'studentName' => '陆诚',
                'status'      => '已预警'
            ),
            1 => array(
                'studentName' => '龚雨倩',
                'status'      => '已预警'
            ),
            2 => array(
                'studentName' => '王嘉伟',
                'status'      => '已预警'
            ),
            3 => array(
                'studentName' => '高晓晴',
                'status'      => '已预警'
            ),
            4 => array(
                'studentName' => '李心云',
                'status'      => '已处理'
            ),
            5 => array(
                'studentName' => '王有利',
                'status'      => '已处理'
            ),
            6 => array(
                'studentName' => '程恒',
                'status'      => '已处理'
            ),
        );

        // 活动参与预警
        $activityJoinWarnings = array(
            0 => array(
                'activityName' => 'CAD与BIM建模实训',
                'data'         => '42.5'
            ),
            1 => array(
                'activityName' => 'VR交通安全行',
                'data'         => '44.8'
            ),
            2 => array(
                'activityName' => '二手车鉴定与评估',
                'data'         => '47.5'
            ),
            3 => array(
                'activityName' => '企业质量认证与管理',
                'data'         => '47.9'
            ),
            4 => array(
                'activityName' => '体育科研方法',
                'data'         => '48.8'
            ),
            5 => array(
                'activityName' => '供应链项目应用',
                'data'         => '49.2'
            ),
            6 => array(
                'activityName' => '特种设备基础',
                'data'         => '49.6'
            ),
        );

        // 实时上课状态
        $records = $this->getLogService()->searchLogs(
            array('action' => 'start_course_lesson'),
            'created',
            0,
            10
        );
        foreach ($records as &$record) {
            $courseId = $record['data']['courseId'];
            $course = $this->getCourseService()->getCourse($courseId);
            $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
            $record['courseSetTitle'] = $courseSet['title'];
        }
        $userIds = ArrayToolkit::column($records, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        // 备课老师人数
        $haveTeachingPlanTeachersCount = $this->getPrepareCourseLogService()->countCurrentTermTeachersByOrgCode($orgCode);
        // 老师总人数
        $teachersCounts = $this->getCourseService()->countAllTeachersByOrgCode($orgCode);

        return $this->render('CustomBundle:databoard:detail.html.twig', array(
            'users'                  => $users,
            'records'                => $records,
            'collegeCategories'      => $this->buildCollegesCategories(),
            'currentOrgCode'         => $orgCode,
            'weekAttendWarningRanks' => $weekAttendWarningRanks,
            'rankings'               => $rankings,
            'studentAttendWarnings'   => $studentAttendWarnings,
            'activityJoinWarnings'    => $activityJoinWarnings,
            'haveTeachingPlanTeachersCount' => $haveTeachingPlanTeachersCount,
            'teachersCounts' => $teachersCounts,
        ));
    }

    protected function renderCollegePage($request, $orgCode)
    {
        // 该学院的开课数
        $startCourseCount = $this->getPrepareCourseLogService()->countCurrentTermLogByOrgCode($orgCode);

        // 该学院的课堂报告数据
        $classReportCounts = $this->classReportStatistic($request);

        // 备课老师人数
        $haveTeachingPlanTeachersCount = $this->getPrepareCourseLogService()->countCurrentTermTeachersByOrgCode($orgCode);
        // 老师总人数
        $teachersCounts = $this->getCourseService()->countAllTeachersByOrgCode($orgCode);

        return $this->render('CustomBundle:databoard:detail-org.html.twig', array(
            'collegeCategories' => $this->buildCollegesCategories(),
            'startCourseCount'  => $startCourseCount,
            'classReportCounts' => $classReportCounts,
            'dataInfo'          => $this->getDateInfo(),
            'haveTeachingPlanTeachersCount' => $haveTeachingPlanTeachersCount,
            'teachersCounts' => $teachersCounts,
        ));
    }

    public function collegesAttendTrendAction(Request $request)
    {
        // 出勤总趋势
        $attendTrend = array(
            0  => array(
                'day'  => '周日',
                'data' => '69.4',
            ),
            1  => array(
                'day'  => '周一',
                'data' => '89.3',
            ),
            2  => array(
                'day'  => '周二',
                'data' => '84.7',
            ),
            3  => array(
                'day'  => '周三',
                'data' => '85.8',
            ),
            4  => array(
                'day'  => '周四',
                'data' => '81.9',
            ),
            5  => array(
                'day'  => '周五',
                'data' => '83.4',
            ),
            6  => array(
                'day'  => '周六',
                'data' => '75.8',
            ),
        );

        return $this->createJsonResponse($attendTrend);
    }

    public function fileStatisticAction(Request $request)
    {
        $orgCode = $request->query->get('orgCode', '1.');
        $startTime = $request->query->get('startTime', date('Y-m-d', time()));
        $endTime = $request->query->get('endTime', date('Y-m-d', time()));

        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime.'+1 day');
        $fileStatistics = $this->getUploadFileService()->countFileByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);
        $fileStatistics = $this->buildFileStatistics($fileStatistics);

        return $this->createJsonResponse($fileStatistics);
    }

    protected function buildFileStatistics($fileStatistics)
    {
        $statistic = array(
            'video' => array('name' => '视频', 'count' => 0),
            'audio' => array('name' => '音频', 'count' => 0),
            'document' => array('name' => '文档', 'count' => 0),
            'ppt' => array('name' => 'ppt', 'count' => 0),
        );

        array_walk(
            $fileStatistics,
            function ($fileStatistic) use (&$statistic) {
                if (array_key_exists($fileStatistic['type'], $statistic)) {
                    $statistic[$fileStatistic['type']]['count'] = $fileStatistic['count'];
                }
            }
        );

        return array(
            'sourceTypes' => ArrayToolkit::column($statistic, 'name'),
            'sourceCounts' => ArrayToolkit::column($statistic, 'count'),
        );
    }

    public function shareCountAction(Request $request)
    {
        $orgCode = $request->query->get('orgCode', '1.');
        $startTime = $request->query->get('startShareTime', date('Y-m-d', time()));
        $endTime = $request->query->get('endShareTime', date('Y-m-d', time()));

        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime.'+1 day');

        $count = $this->getCourseShareService()->countShareByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);

        return $this->createJsonResponse($count);
    }


    protected function buildCollegesCategories()
    {
        $collegeCategories = array();
        // 全校的org
        $schoolOrg = $this->getOrgService()->getOrgByOrgCode('1.');
        $collegeCategories[] = array(
            'id' => $schoolOrg['id'],
            'name' => '全部',
            'orgCode' => $schoolOrg['orgCode']
        );

        // 学院的列表
        $collegesOrgs = $this->buildCollegesOrgs();
        foreach ($collegesOrgs as $org) {
            $collegeCategories[] = array(
                'id' => $org['id'],
                'name' => $org['name'],
                'orgCode' => $org['orgCode'],
            );
        }

        return $collegeCategories;
    }

    protected function classReportStatistic($request)
    {
        $orgCode = $request->query->get('orgCode', '1.');
        $dateInfo = $this->getDateInfo();

        $dayCount = $this->getCourseLessonService()->countClassReportsByOrgCodeAndTimeRange($orgCode, strtotime($dateInfo['today']), strtotime($dateInfo['today'].'+1 day'));
        $weekCount = $this->getCourseLessonService()->countClassReportsByOrgCodeAndTimeRange($orgCode, strtotime($dateInfo['currentWeekStart']), strtotime($dateInfo['currentWeekEnd'].'+1 day'));
        $monthCount = $this->getCourseLessonService()->countClassReportsByOrgCodeAndTimeRange($orgCode, strtotime($dateInfo['currentMonthStart']), strtotime($dateInfo['currentMonthEnd'].'+1 day'));

        return array(
            'day' => $dayCount,
            'week' => $weekCount,
            'month' => $monthCount,
        );
    }

    protected function getDateInfo()
    {
        $today = date('Y-m-d', time());
        $currentWeekStart = date('Y-m-d', strtotime(date('Y-m-d').'-1 Week Monday'));

        if (date('Y-m-d', strtotime($today.'-7 day')) == $currentWeekStart) {
            $currentWeekStart = $today;
        }
        $currentWeekEnd = date('Y-m-d', strtotime($currentWeekStart.'+6 day'));

        $currentMonthStart = date('Y-m-01', time());
        $currentMonthEnd = date('Y-m-d', strtotime($currentMonthStart.'+1 month -1 day'));

        return array(
            'today' => $today,
            'currentWeekStart' => $currentWeekStart,
            'currentWeekEnd' => $currentWeekEnd,
            'currentMonthStart' => $currentMonthStart,
            'currentMonthEnd' => $currentMonthEnd,
        );
    }

    protected function buildCollegesOrgs()
    {
        $teachingUnitOrg = $this->getOrgService()->getOrgByCode(300);
        $collegesOrgs = $this->getOrgService()->findOrgsByParentId($teachingUnitOrg['id']);

        return $collegesOrgs;
    }

    protected function getTeacherCourseStatisticsService()
    {
        return $this->createService('CustomBundle:Statistics:TeacherCourseStatisticsService');
    }

    protected function getFacultyService()
    {
        return $this->createService('CustomBundle:User:FacultyService');
    }

    protected function getUserService()
    {
        return $this->createService('CustomBundle:User:UserService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getUploadFileService()
    {
        return $this->createService('CustomBundle:File:UploadFileService');
    }

    protected function getCourseShareService()
    {
        return $this->createService('CustomBundle:Course:CourseShareService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getPrepareCourseLogService()
    {
        return $this->createService('CustomBundle:Course:PrepareCourseLogService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getOrgService()
    {
        return $this->createService('CustomBundle:Org:OrgService');
    }

    protected function getLogService()
    {
        return $this->createService('CustomBundle:System:LogService');
    }

    protected function getD()
    {
        return $this->createService('CustomBundle:Lesson:TeachingAimActivityService');
    }
}