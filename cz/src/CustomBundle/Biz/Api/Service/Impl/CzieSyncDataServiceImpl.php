<?php
namespace CustomBundle\Biz\Api\Service\Impl;

use Monolog\Logger;
use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Monolog\Handler\StreamHandler;
use CustomBundle\Biz\Api\Client\CzieClient;
use CustomBundle\Biz\Api\Service\CzieSyncDataService;

class CzieSyncDataServiceImpl extends BaseService implements CzieSyncDataService
{
    private $logger;

    public function saveApiData()
    {
        $this->getLogger()->info('开始与教务系统同步数据……');
        $this->syncTerms();
        $this->syscCourseSet();
        $this->syscTeacherOrgs();
        $this->syscTeachers();
        $this->saveApiStudents();
        $this->saveApiCourses();
        $this->markCurrentTerm('17-18-1');
        $this->getLogger()->info('接口数据保存完毕！');
    }

    public function syncTerms()
    {
        $this->getLogger()->info('开始同步学期数据……');
        try {
            $client = $this->getCzieClient();
            $terms  = $client->getTerms();
            $this->saveTerms($terms);
            $currrentTermCode = $client->getCurrentTerm();
            $this->markCurrentTerm($currrentTermCode);
            $this->getLogger()->info('学期数据同步完成！');
        } catch (\Exception $e) {
            $this->getLogger()->error("学期数据同步失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    private function saveTerms(array $terms)
    {
        $termDao = $this->getTermDao();

        foreach ($terms as $apiTerm) {
            if (empty($termDao->getByShortCode($apiTerm['xq']))) {
                $term = array(
                    'title'     => $this->makeTermTitle($apiTerm['xq']),
                    'current'   => 0,
                    'shortCode' => $apiTerm['xq'],
                    'longCode'  => $apiTerm['xnxq']
                );
                $termDao->create($term);
            }
        }
    }

    private function markCurrentTerm($code)
    {
        $this->getLogger()->info('开始获取当前学期……');
        $termDao = $this->getTermDao();
        $termDao->reset();
        $term = $termDao->getByShortCode($code);
        $termDao->update($term['id'], array(
            'current' => 1
        ));
        $this->getLogger()->info('获取当前学期成功！');
    }

    private function makeTermTitle($code)
    {
        list($year1, $year2, $quarter) = explode('-', $code);
        $title                         = $year1.'~'.$year2.'学年';

        if ($quarter == 1) {
            $title = $title.'上学期';
        } else {
            $title = $title.'下学期';
        }

        return $title;
    }

    public function syscCourseSet()
    {
        $this->getLogger()->info('开始同步学科数据……');
        try {
            $client   = $this->getCzieClient();
            $subjects = $client->getCourseSet();
            $dao      = $this->getApiCourseSetDao();

            foreach ($subjects as $subject) {
                if (empty($dao->getByKcdm($subject['kcdm']))) {
                    $apiCourseSet = $subject;
                    $apiCourseSet = ArrayToolkit::parts($apiCourseSet, array('xbdm', 'xbmc', 'kcdm', 'kcmc'));
                    $dao->create($apiCourseSet);
                }
            }

            $this->getLogger()->info('学科数据同步完成……');
        } catch (\Exception $e) {
            $this->getLogger()->error("学科数据同步失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    public function saveApiStudents()
    {
        $this->getLogger()->info('开始从教务系统获取学生数据……');
        try {
            $client     = $this->getCzieClient();
            $students   = $client->getStudents();
            $studentDao = $this->getStudentDao();

            foreach ($students as $student) {
                $student['yx'] = $this->trimAll($student['yx']);

                if (empty($studentDao->getByCode($student['xh']))) {
                    $faculty              = $this->makeFaculty($student);
                    $major                = $this->makeMajor($student, $faculty);
                    $classroom            = $this->makeClassroom($student, $major);
                    $param                = $student;
                    $param['facultyId']   = $faculty['id'];
                    $param['majorId']     = $major['id'];
                    $param['classroomId'] = $classroom['id'];
                    $param                = ArrayToolkit::parts($param, array('xh', 'xm', 'xbdm', 'yx', 'yxdm', 'yxmc', 'zydm', 'zymc', 'bh', 'bjmc', 'rxnf', 'xz', 'xjzt', 'gxsj', 'jlzt', 'px', 'classroomId', 'majorId', 'facultyId', 'userId'));
                    $studentDao->create($param);
                }
            }

            $this->getLogger()->info('学生基础数据保存完成！');
        } catch (\Exception $e) {
            $this->getLogger()->error("学生基础数据保存失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    public function partRegisterStudents()
    {
        $this->getLogger()->info('开始同步学生基础数据……');
        try {
            $studentDao = $this->getStudentDao();
            $students   = $studentDao->findNotRegister();
            $count      = count($students);
            $this->getLogger()->info("预计会创建{$count}位学员！");
            $num = 0;

            foreach ($students as $student) {
                $user = $this->studentRegister($student);
                $studentDao->update($student['id'], array(
                    'userId' => $user['id']
                ));
                $num++;
            }

            $this->getLogger()->info("当前一共创建了{$num}位学员！");
        } catch (\Exception $e) {
            $this->getLogger()->error("学生基础数据同步失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    public function syscTeacherOrgs()
    {
        $this->getLogger()->info('开始同步教师组织机构……');
        try {
            $client        = $this->getCzieClient();
            $apiOrgs       = $client->getTeacherOrgs();
            $teacherOrgDao = $this->getTeacherOrgDao();

            foreach ($apiOrgs as $apiOrg) {
                if (empty($teacherOrgDao->getByCode($apiOrg['department_key']))) {
                    $org = $apiOrg;
                    $org = ArrayToolkit::parts($org, array('department_key', 'department_name', 'parent_key', 'division_key', 'division_name', 'remark', 'sort_num'));
                    $teacherOrgDao->create($org);
                }
            }

            $this->makeOrg('-1', 1);
            $this->getLogger()->info('教师组织机构同步完成！');
        } catch (\Exception $e) {
            $this->getLogger()->error("教师组织机构数据同步失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    public function syscTeachers()
    {
        $this->getLogger()->info('开始同步教师基础数据……');
        try {
            $client        = $this->getCzieClient();
            $teachers      = $client->getTeachers();
            $teacherDao    = $this->getTeacherDao();
            $teacherOrgDao = $this->getTeacherOrgDao();
            $orgService    = $this->getOrgService();
            $userService   = $this->getUserService();

            foreach ($teachers as $teacher) {
                $teacher['yx'] = $this->trimAll($teacher['yx']);
                $user          = $this->teacherRegister($teacher);
                $apiOrg        = $teacherOrgDao->getByCode($teacher['szbmm']);

                if (!empty($apiOrg)) {
                    $org = $orgService->getOrg($apiOrg['orgId']);

                    if (!empty($org)) {
                        $userService->changeUserOrg($user['id'], $org['orgCode']);
                    }
                }

                $dbTeacher = $teacherDao->getByCode($teacher['zgh']);

                if (empty($dbTeacher)) {
                    $param           = $teacher;
                    $param['userId'] = $user['id'];
                    $param           = ArrayToolkit::parts($param, array('zgh', 'xm', 'xb', 'yx', 'szbmm', 'dqzt', 'gxsj', 'jlzt', 'sortnum', 'userId'));
                    $teacherDao->create($param);
                } elseif (!isset($dbTeacher['userId'])) {
                    $teacherDao->update($dbTeacher['id'], array(
                        'userId' => $user['id']
                    ));
                }
            }

            $this->getLogger()->info('教师基础数据同步完成！');
        } catch (\Exception $e) {
            $this->getLogger()->error("教师基础构数据同步失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    public function partSyscCourses()
    {
        ini_set('memory_limit', '-1');
        $this->getLogger()->info('开始同步教学计划……');
        $apiCourseDao  = $this->getApiCourseDao();
        $currentTerm   = $this->getTermDao()->getCurrentTerm();
        $noMakeCourses = $apiCourseDao->findNoMakeByTerm($currentTerm['shortCode']);
        $this->getLogger()->info('当前预计有'.count($noMakeCourses).'个教学计划需要被创建！');

        foreach ($noMakeCourses as $apiCourse) {
            $this->makeCourse($apiCourse);
        }

        $this->getLogger()->info('分段同步教学计划完成');
    }

    public function syscCourseMembersByCourseId($courseId)
    {
    }

    public function syscCourseMembers($apiCourse)
    {
        try {
            $this->getLogger()->info("开始同步课程{$apiCourse['courseId']}的成员……");
            $client             = $this->getCzieClient();
            $members            = $client->getCourseMembers($apiCourse['xq'], $apiCourse['kch'], $apiCourse['hb'], $apiCourse['lx'], $apiCourse['lbdm']);
            $apiCourseMemberDao = $this->getApiCourseMemberDao();
            $this->getLogger()->info('当前课程一共有'.count($members).'位学员！');
            if (count($members) > 0) {
                foreach ($members as $member) {
                    if (empty($apiCourseMemberDao->getByCourseIdAndNo($apiCourse['courseId'], $member['xh']))) {
                        $param             = $member;
                        $param['courseId'] = $apiCourse['courseId'];
                        $user              = $this->getUserService()->getUserByNickname($member['xh']);

                        if (empty($user) && !empty($member['xh'])) {
                            $registerUser = array(
                                'email'    => $member['xh'].'@czie.edu.cn',
                                'nickname' => $member['xh'],
                                'password' => $member['xh'],
                                'truename' => $this->trimAll($member['xm']),
                                'gender'   => $member['xb'] == '男' ? 'male' : 'female',
                                'type'     => 'import'
                            );
                            $user = $this->getAuthService()->register($registerUser);
                        }

                        $param['userId'] = $user['id'];

                        if (!$this->getMemberService()->isCourseMember($apiCourse['courseId'], $user['id'])) {
                            $member            = $this->getMemberService()->becomeStudent($apiCourse['courseId'], $user['id']);
                            $param['memberId'] = $member['id'];
                        }

                        $param = ArrayToolkit::parts($param, array('xq', 'bh', 'bj', 'xh', 'xm', 'lbdh', 'kcxh', 'xs', 'xf', 'xb', 'courseId', 'memberId', 'userId'));
                        $apiCourseMemberDao->create($param);
                    }
                }
            }

            $this->getLogger()->info("课程{$apiCourse['courseId']}的成员同步完毕！");
        } catch (\Exception $e) {
            $this->getLogger()->error("课程{$apiCourse['courseId']}的成员同步失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    private function makeCourse($apiCourse)
    {
        $this->beginTransaction();
        try {
            $apiCourseDao      = $this->getApiCourseDao();
            $alreadyMakeCourse = $apiCourseDao->getAlreadyMake($apiCourse['xq'], $apiCourse['kcdm'], $apiCourse['hb'], $apiCourse['skbj'], $apiCourse['lx'], $apiCourse['lbdm']);
            $apiCourses        = $this->getApiCourseDao()->findByParam($apiCourse['xq'], $apiCourse['kcdm'], $apiCourse['hb'], $apiCourse['skbj'], $apiCourse['lx'], $apiCourse['lbdm']);

            if (empty($apiCourses)) {
                return null;
            }

            if (!empty($alreadyMakeCourse)) {
                $course = $this->getCourseService()->getCourse($alreadyMakeCourse['courseId']);
            } else {
                $masterCourse = $apiCourses[0];
                $masterUser   = $this->getUserService()->getUserByNickname($masterCourse['jsdm']);

                if (empty($masterUser)) {
                    $this->getLogger()->debug($masterCourse['jsdm'].'老师不存在,需要创建');
                    $registerUser = array(
                        'email'    => $masterCourse['jsdm'].'@czie.edu.cn',
                        'nickname' => $masterCourse['jsdm'],
                        'password' => $masterCourse['jsdm'],
                        'truename' => $this->trimAll($masterCourse['jsmc']),
                        'type'     => 'import'
                    );
                    $masterUser = $this->getAuthService()->register($registerUser);
                    $roles[]    = 'ROLE_TEACHER';
                    array_push($roles, 'ROLE_USER');
                    $this->getUserService()->changeUserRoles($masterUser['id'], $roles);

                    $this->getLogService()->info('user', 'add', "通过接口导入新用户(老师) {$masterUser['nickname']} ({$masterUser['id']})");
                }

                $courseSet = $this->makeCourseSet($apiCourse, $masterUser['id']);
                $course    = $this->getCourseService()->createCourse(array(
                    'courseSetId'   => $courseSet['id'],
                    'title'         => $apiCourse['skbj'],
                    'expiryMode'    => 'forever',
                    'expiryDays'    => 0,
                    'learnMode'     => 'freeMode',
                    'isDefault'     => 1,
                    'isFree'        => 1,
                    'type'          => 'instant',
                    'termCode'      => $apiCourse['xq'],
                    'serializeMode' => $courseSet['serializeMode'],
                    'status'        => 'published'
                ));
                $lessinNum = intval($apiCourse['xs']);

                if ($lessinNum > 0) {
                    if ($lessinNum % 2 == 1) {
                        $lessinNum = $lessinNum + 1;
                    }

                    $lessinNum = $lessinNum / 2;
                    $this->getCourseLessonService()->batchCreateCourseLessons($course['id'], $lessinNum);
                }

                $apiCourse['courseId'] = $course['id'];
                $this->syscCourseMembers($apiCourse);
            }

            $teachers = array();

            foreach ($apiCourses as $apiCourse) {
                $user = $this->getUserService()->getUserByNickname($apiCourse['jsdm']);

                if (empty($user)) {
                    $this->getLogger()->debug($apiCourse['jsdm'].'老师不存在,需要创建');
                    $registerUser = array(
                        'email'    => $apiCourse['jsdm'].'@czie.edu.cn',
                        'nickname' => $apiCourse['jsdm'],
                        'password' => $apiCourse['jsdm'],
                        'truename' => $this->trimAll($apiCourse['jsmc']),
                        'type'     => 'import'
                    );
                    $user    = $this->getAuthService()->register($registerUser);
                    $roles[] = 'ROLE_TEACHER';
                    array_push($roles, 'ROLE_USER');
                    $this->getUserService()->changeUserRoles($user['id'], $roles);

                    $this->getLogService()->info('user', 'add', "通过接口导入新用户(老师) {$user['nickname']} ({$user['id']})");
                }

                $teachers[] = array(
                    'id'        => $user['id'],
                    'isVisible' => 1
                );

                $apiCourseDao->update($apiCourse['id'], array('courseId' => $course['id']));
            }

            if (count($teachers) > 0) {
                $this->getMemberService()->setCourseTeachers($course['id'], $teachers);
            }

            $this->commit();
            return $course;
        } catch (\Exception $e) {
            $this->rollback();
            $this->getLogger()->error("{$apiCourse['xq']}, {$apiCourse['kch']}, {$apiCourse['hb']}, {$apiCourse['lx']}, {$apiCourse['lbdm']}课程同步失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    private function trimAll($str)
    {
        $result = str_replace(array(" ", "　", "\t", "\n", "\r"), array("", "", "", "", ""), $str);
        return $result;
    }

    private function makeCourseSet($apiCourse, $userId)
    {
        $courseSetService = $this->getCourseSetService();
        $courseSet        = $courseSetService->getByUserIdAndCourseNo($userId, $apiCourse['kcdm']);

        if (empty($courseSet)) {
            $courseSet = $this->getCourseSetService()->createInstantCourseSet(array(
                'title'    => $apiCourse['kcmc'],
                'userId'   => $userId,
                'courseNo' => $apiCourse['kcdm'],
                'type'     => 'instant'
            ));
            $this->getLogger()->debug('老师的courseSet创建成功!');
        }

        return $courseSet;
    }

    private function saveApiCourses()
    {
        $this->getLogger()->info('开始从教务系统获取教学计划！');
        try {
            $currentTerm  = $this->getTermDao()->getCurrentTerm();
            $client       = $this->getCzieClient();
            $apiCourses   = $client->getCoursesByTerm($currentTerm['shortCode']);
            $apiCourseDao = $this->getApiCourseDao();

            foreach ($apiCourses as $apiCourse) {
                if (empty($apiCourseDao->getByParam($apiCourse['xq'], $apiCourse['kcdm'], $apiCourse['hb'], $apiCourse['jsdm'], $apiCourse['zjjs'], $apiCourse['skbj'], $apiCourse['lx'], $apiCourse['lbdm']))) {
                    $param           = $apiCourse;
                    $param['api_id'] = $apiCourse['id'];
                    unset($param['id']);
                    $param = ArrayToolkit::parts($param, array('xq', 'kcdm', 'kch', 'kcmc', 'lbdh', 'zxs', 'xs', 'xf', 'syxs', 'sjxs', 'jsmc', 'jsdm', 'hbs', 'hb', 'skjs', 'skbj', 'lx', 'lbdm', 'ksfs', 'zjjs', 'jssf', 'xkrs', 'kcxbdm', 'api_id', 'kcdgbh'));
                    $apiCourseDao->create($param);
                }
            }

            $this->getLogger()->info('教学计划接口的原始数据保存完毕！');
        } catch (\Exception $e) {
            $this->getLogger()->error("教学计划接口的原始数据保存失败：");
            $this->getLogger()->error($e->getMessage());
            throw $e;
        }
    }

    private function makeOrg($parentCode, $parentId)
    {
        $teacherOrgDao = $this->getTeacherOrgDao();
        $teacherOrgs   = $teacherOrgDao->findByParentCode($parentCode);

        if (empty($teacherOrgs)) {
            return;
        }

        $orgService = $this->getOrgService();

        foreach ($teacherOrgs as $teacherOrg) {
            if (!isset($teacherOrg['orgId'])) {
                $org = $orgService->getOrgByCode($teacherOrg['department_key']);

                if (empty($org)) {
                    $org = $orgService->createOrg(array(
                        'name'     => $teacherOrg['department_name'],
                        'code'     => $teacherOrg['department_key'],
                        'parentId' => $parentId
                    ));
                }

                $teacherOrg = $teacherOrgDao->update($teacherOrg['id'], array('orgId' => $org['id']));
            }

            $this->makeOrg($teacherOrg['department_key'], $teacherOrg['orgId']);
        }
    }

    private function studentRegister($student)
    {
        $user = $this->getUserService()->getUserByNickname($student['xh']);

        if (!empty($user)) {
            return $user;
        }

        $registerUser = array(
            'email'    => $student['yx'],
            'nickname' => $student['xh'],
            'password' => $student['xh'],
            'truename' => $student['xm'],
            'gender'   => $student['xbdm'] == '1' ? 'male' : 'female',
            'type'     => 'import'
        );
        $user = $this->getAuthService()->register($registerUser);
        $this->getLogService()->info('user', 'add', "通过接口导入新用户 {$user['nickname']} ({$user['id']})");
    }

    private function teacherRegister($teacher)
    {
        $user = $this->getUserService()->getUserByNickname($teacher['zgh']);
        $org  = $this->getOrgService()->getOrgByCode($teacher['szbmm']);

        if (!empty($user)) {
            return $user;
        }

        $registerUser = array(
            'email'    => $teacher['yx'],
            'nickname' => $teacher['zgh'],
            'password' => $teacher['zgh'],
            'truename' => $teacher['xm'],
            'gender'   => $teacher['xb'] == '男' ? 'male' : 'female',
            'type'     => 'import'
        );
        $user = $this->getAuthService()->register($registerUser);

        if (!empty($org)) {
            $this->getUserService()->changeUserOrg($user['id'], $org['orgCode']);
        }

        $roles[] = 'ROLE_TEACHER';
        array_push($roles, 'ROLE_USER');
        $this->getUserService()->changeUserRoles($user['id'], $roles);

        $this->getLogService()->info('user', 'add', "通过接口导入新用户(老师) {$user['nickname']} ({$user['id']})");
    }

    private function makeFaculty($student)
    {
        $dao     = $this->getFacultyDao();
        $faculty = $dao->getByCode($student['yxdm']);

        if (empty($faculty)) {
            $faculty = $dao->create(
                array(
                    'code' => $student['yxdm'],
                    'name' => $student['yxmc']
                )
            );
        }

        return $faculty;
    }

    private function makeMajor($student, $faculty)
    {
        $dao   = $this->getMajorDao();
        $major = $dao->getByCode($student['zydm']);

        if (empty($major)) {
            $major = $dao->create(
                array(
                    'code'      => $student['zydm'],
                    'name'      => $student['zymc'],
                    'facultyId' => $faculty['id']
                )
            );
        }

        return $major;
    }

    private function makeClassroom($student, $major)
    {
        $dao       = $this->getClassroomDao();
        $classroom = $dao->getByCode($student['bh']);

        if (empty($classroom)) {
            $classroom = $dao->create(
                array(
                    'code'    => $student['bh'],
                    'name'    => $student['bjmc'],
                    'majorId' => $major['id']
                )
            );
        }

        return $classroom;
    }

    public function getLastJob()
    {
        return $this->getSyncJobDao()->getLastJob();
    }

    public function createSyncDataJob()
    {
        $currentJob = $this->getSyncJobDao()->getLastJob();

        if (!empty($currentJob) &&
            ($currentJob['status'] == 'created' || $currentJob['status'] == 'syncing')) {
            throw new \RuntimeException('已经有一个正在进行中的同步任务了！');
        }

        $job = $this->getSyncJobDao()->create(
            array(
                'opUserId' => $this->getCurrentUser()['id'],
                'status'   => 'created'
            )
        );
        $jobParams = array(
            'jobId' => $job['id']
        );
        $this->SchedulerService()->register(array(
            'name' => 'CzieSyncDataJob',
            'class' => 'CustomBundle\\Biz\\Api\\Job\\SyncDataJob',
            'expression' => time() + 10,
            'args' => $jobParams,
            'priority' => 100,
            'misfire_threshold' => 300,
            'misfire_policy' => 'missed'
        ));

        return $job;
    }

    public function execJob($id)
    {
        try {
            $job = $this->getSyncJobDao()->get($id);

            if (empty($job)) {
                $this->getLogger()->info("同步定时任务job不存在{$id}");
                return;
            }
            $this->getLogger()->info("同步定时任务status:{$job['status']}");

            if ($job['status'] == 'created') {
                $this->getSyncJobDao()->update($id, array('status' => 'syncing'));
                $this->saveApiData();
                $this->createTrontab($id);
            } elseif ($job['status'] == 'syncing') {
                $this->partRegisterStudents();

                if (!$this->isStudentSyncComplete()) {
                    $this->createTrontab($id);
                    return;
                }

                $this->partSyscCourses();

                if (!$this->isCourseSyncComplete()) {
                    $this->createTrontab($id);
                    return;
                }

                $this->getLogger()->info("数据同步job执行完成");
                $this->getSyncJobDao()->update($id, array('status' => 'succeed'));
            }
        } catch (\Exception $e) {
            $this->getSyncJobDao()->update($id, array('status' => 'fail'));
            $this->getLogger()->error("数据同步job执行失败：");
            $this->getLogger()->error($e->getMessage());
        }
    }

    private function createTrontab($jobId)
    {
        $jobParams = array(
            'jobId' => $jobId
        );
        $this->SchedulerService()->register(array(
            'name' => 'CzieSyncDataJob',
            'class' => 'CustomBundle\\Biz\\Api\\Job\\SyncDataJob',
            'expression' => time() + 10,
            'args' => $jobParams,
            'priority' => 100,
            'misfire_threshold' => 300,
            'misfire_policy' => 'missed'
        ));
    }

    private function isStudentSyncComplete()
    {
        $num = $this->getStudentDao()->countNotRegister();
        return $num == 0 ? true : false;
    }

    private function isCourseSyncComplete()
    {
        $currentTerm = $this->getTermDao()->getCurrentTerm();
        $apiCourses  = $this->getApiCourseDao()->findNoMakeByTerm($currentTerm['shortCode']);
        $num         = count($apiCourses);
        return $num == 0 ? true : false;
    }

    private function getCzieClient()
    {
        return CzieClient::instance();
    }

    protected function getTermDao()
    {
        return $this->createDao('CustomBundle:Course:TermDao');
    }

    protected function getApiCourseSetDao()
    {
        return $this->createDao('CustomBundle:Course:CzieApiCourseSetDao');
    }

    protected function getApiCourseDao()
    {
        return $this->createDao('CustomBundle:Course:ApiCourseDao');
    }

    protected function getApiCourseMemberDao()
    {
        return $this->createDao('CustomBundle:Course:ApiCourseMemberDao');
    }

    protected function getFacultyDao()
    {
        return $this->createDao('CustomBundle:User:FacultyDao');
    }

    protected function getMajorDao()
    {
        return $this->createDao('CustomBundle:User:MajorDao');
    }

    protected function getTeacherDao()
    {
        return $this->createDao('CustomBundle:User:TeacherDao');
    }

    protected function getClassroomDao()
    {
        return $this->createDao('CustomBundle:User:ClassroomDao');
    }

    protected function getStudentDao()
    {
        return $this->createDao('CustomBundle:User:StudentDao');
    }

    protected function getTeacherOrgDao()
    {
        return $this->createDao('CustomBundle:User:TeacherOrgDao');
    }

    protected function getOrgService()
    {
        return $this->createService('Org:OrgService');
    }

    protected function getAuthService()
    {
        return $this->createService('User:AuthService');
    }

    private function SchedulerService()
    {
        return $this->createService('Scheduler:SchedulerService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    protected function getRoleService()
    {
        return $this->createService('Role:RoleService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getSyncJobDao()
    {
        return $this->createDao('CustomBundle:Api:SyncJobDao');
    }

    protected function getLogger($name = 'czie-sync')
    {
        if ($this->logger) {
            return $this->logger;
        }

        $this->logger = new Logger($name);
        $this->logger->pushHandler(new StreamHandler($this->biz['log_directory'].'/czie-api.log', Logger::DEBUG));

        return $this->logger;
    }
}
