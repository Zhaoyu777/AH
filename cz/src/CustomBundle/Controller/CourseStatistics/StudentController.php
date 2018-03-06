<?php

namespace CustomBundle\Controller\CourseStatistics;

use AppBundle\Common\Paginator;
use AppBundle\Common\ExportHelp;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Biz\Statistics\Analyze\DatasAnalyze;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;

class StudentController extends BaseController
{
    public function indexAction(Request $request, $courseSetId, $courseId)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
        $course = $this->getCourseService()->tryManageCourse($courseId);

        $multiAnalysis = $this->getStudentCourseStatisticsService()->getStudentsMultiAnalysisByCourseId($courseId);
        $multiAnalysis = implode($multiAnalysis, ',');

        return $this->render('course-manage/dashboard/student-learn-statistics.html.twig', array(
            'courseSet' => $courseSet,
            'course' => $course,
            'multiAnalysis' => $multiAnalysis
        ));
    }

    public function searchAction(Request $request, $courseId)
    {
        $this->getCourseService()->tryManageCourse($courseId);

        $conditions = $request->query->all();
        $conditions['courseId'] = $courseId;
        $orderBy = $conditions['orderBy'];
        $isDesc = $conditions['isDesc'];

        $conditions = $this->prepareConditions($conditions);

        if (isset($conditions['userIds']) && empty($conditions['userIds'])) {
            $paginator = new Paginator(
                $this->get('request'),
                0,
                5
            );

            $studentCourseStatistics = array();
        } else {
            $paginator = new Paginator(
                $this->get('request'),
                $this->getStudentCourseStatisticsService()->countStudentsCourseStatistics($conditions),
                5
            );

            $order = $this->prepareOrder($orderBy, $isDesc);

            $studentCourseStatistics = $this->getStudentCourseStatisticsService()->searchStudentsCourseStatistics(
                $conditions,
                $order,
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );
        }

        return $this->render('course-manage/dashboard/student-learn-statistics-table.html.twig', array(
            'paginator' => $paginator,
            'studentsStatistics' => $this->completeStatistics($studentCourseStatistics)
        ));
    }

    public function exportAction(Request $request, $courseId)
    {
        list($start, $limit, $allowCount) = ExportHelp::getMagicExportSetting($request);
        $fileName = $request->query->get('fileName');
        $sumCount = $request->query->get('sumCount');

        if (empty($fileName)) {
            $course = $this->getCourseService()->getCourse($courseId);
            $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

            $fileName = ExportHelp::addFileTitle($request, '学生学习情况导出.csv', '姓名,学号,班级,学院,应出勤次数,实际出勤次数,积分,课堂积极性,课外积极性,平时成绩');
        }

        $conditions = array('courseId' => $courseId);

        if (empty($sumCount)) {
            $count = $this->getStudentCourseStatisticsService()->countStudentsCourseStatistics($conditions);
            $sumCount = min($count, $allowCount);
        }

        $method = ExportHelp::getNextMethod($start, $sumCount);

        if ($method === 'export') {
            $course = $this->getCourseService()->getCourse($courseId);
            $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

            return ExportHelp::exportCsv($request, '《'.$courseSet['title'].'》学生学习情况导出.csv', $fileName);
        } elseif ($method === 'getData') {
            $studentCourseStatistics = $this->getStudentCourseStatisticsService()->searchStudentsCourseStatistics(
                $conditions,
                array(),
                $start,
                $limit
            );

            $userIds = ArrayToolkit::column($studentCourseStatistics, 'userId');
            $users = $this->getUserService()->findUsersByIds($userIds);
            $students = $this->getCzieUserService()->findStudentsByUserIds($userIds);
            $students = ArrayToolkit::index($students, 'userId');

            $firstStatustic = reset($studentCourseStatistics);
            $signInCounts = array();
            if (!empty($firstStatustic)) {
                $signInCounts = $this->getSignInService()->countSignInsByCourseIdGroupUserIdBeforeTime($courseId, $firstStatustic['updatedTime']);
            }

            $content = '';
            foreach ($studentCourseStatistics as $studentCourseStatistic) {
                $content .= $users[$studentCourseStatistic['userId']]['truename'].',';
                $content .= $users[$studentCourseStatistic['userId']]['number'].',';
                $content .= empty($students[$studentCourseStatistic['userId']]['bjmc']) ? '--' : $students[$studentCourseStatistic['userId']]['bjmc'];
                $content .= ',';
                $content .= empty($students[$studentCourseStatistic['userId']]['yxmc']) ? '--' : $students[$studentCourseStatistic['userId']]['yxmc'];
                $content .= ',';
                if (empty($signInCounts[$studentCourseStatistic['userId']])) {
                    $signInCount = 0;
                } else {
                    $signInCount = $signInCounts[$studentCourseStatistic['userId']]['count'];
                }

                $attendTimes = round($signInCount * $studentCourseStatistic['studentAttendence'] / 100);
                $content .= $signInCount.'次,';
                $content .= $attendTimes.'次,';
                $content .= $studentCourseStatistic['totalScore'].'分,';
                $content .= $studentCourseStatistic['taskInCompletionRate'].'%,';
                $content .= $studentCourseStatistic['taskOutCompletionRate'].'%,';
                $content .= $studentCourseStatistic['averageGrades'].'分';
                $content .= "\r\n";
            }

            ExportHelp::saveToTempFile($request, $content, $fileName);

            return $this->redirect(
                $this->generateUrl('student_learn_statistic_export',
                array(
                    'courseId' => $courseId,
                    'start' => $start + $limit,
                    'fileName' => $fileName,
                    'sumCount' => $sumCount,
                )
            ));
        }
    }

    protected function completeStatistics($studentCourseStatistics)
    {
        if (empty($studentCourseStatistics)) {
            return array();
        }

        $userIds = ArrayToolkit::column($studentCourseStatistics, 'userId');

        $users = $this->getUserService()->findUsersByIds($userIds);

        foreach ($studentCourseStatistics as &$studentCourseStatistic) {
            if (!empty($studentCourseStatistic['userId'])) {
                $studentCourseStatistic['studentName'] = $users[$studentCourseStatistic['userId']]['truename'];
                $studentCourseStatistic['studentNo'] = $users[$studentCourseStatistic['userId']]['nickname'];
            }

            if (is_null($studentCourseStatistic['studentAttendence'])) {
                $studentCourseStatistic['studentAttendence'] = '--%';
            }

            if (is_null($studentCourseStatistic['taskInCompletionRate'])) {
                $studentCourseStatistic['taskInCompletionRate'] = '---';
            }

            if (is_null($studentCourseStatistic['taskOutCompletionRate'])) {
                $studentCourseStatistic['taskOutCompletionRate'] = '---';
            }

            if (is_null($studentCourseStatistic['averageGrades'])) {
                $studentCourseStatistic['averageGrades'] = '---';
            }
        }

        return $studentCourseStatistics;
    }

    public function searchStudentsCourseStatisticsAction(Request $request, $courseId)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet(1);
        $course = $this->getCourseService()->tryManageCourse(1);

        return $this->render('course-manage/dashboard/student-learn-statistics.html.twig', array(
            'courseSet' => $courseSet,
            'course' => $course,
        ));
    }

    protected function prepareConditions($conditions)
    {
        if (!ArrayToolkit::requireds($conditions, array('courseId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $conditions = ArrayToolkit::parts($conditions, array(
            'courseId',
            'searchCondition',
        ));

        $conditions = array_filter($conditions);

        if (isset($conditions['searchCondition'])) {
            $users = $this->getUserService()->searchAllUsers(
                array('queryField' => $conditions['searchCondition']),
                array(),
                0,
                5
            );
            $conditions['userIds'] = ArrayToolkit::column($users, 'id');

            unset($conditions['searchCondition']);
        }

        return $conditions;
    }

    protected function prepareOrder($orderBy, $isDesc)
    {
        if (empty($orderBy) && empty($isDesc)) {
            return array();
        }

        return array($orderBy => $isDesc == 'true' ? 'DESC' : 'ASC');
    }

    public function courseReportAction(Request $request, $courseId)
    {
        $userId = $request->query->get('userId');

        $statistic = $this->getStudentCourseStatisticsService()->getStudentsCourseStatisticsByUserIdAndCourseId($userId, $courseId);
        $score = $this->getEvaluationService()->getCourseAverageByUserId($courseId, $userId);
        $percentage = $this->getStudentCourseStatisticsService()->getPercentageByUserIdAndCourseId($userId, $courseId);

        return $this->render('my/learning/course/instant-learning-statistics-modal.html.twig', array(
            'statistic' => $statistic,
            'score' => $score,
            'percentage' => $percentage,
        ));
    }

    protected function createInvalidArgumentException($message = '')
    {
        return new InvalidArgumentException($message);
    }

    protected function getEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }

    protected function getUserService()
    {
        return $this->createService('CustomBundle:User:UserService');
    }

    protected function getStudentCourseStatisticsService()
    {
        return $this->createService('CustomBundle:Statistics:StudentCourseStatisticsService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCzieUserService()
    {
        return $this->createService('CustomBundle:User:CzieStudentService');
    }

    protected function getSignInService()
    {
        return $this->createService('CustomBundle:SignIn:SignInService');
    }
}
