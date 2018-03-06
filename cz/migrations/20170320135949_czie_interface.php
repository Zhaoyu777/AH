<?php

use Phpmig\Migration\Migration;

class CzieInterface extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `czie_api_course_set` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `xbdm` varchar(255),
              `xbmc`  varchar(255),
              `kcdm`   varchar(255) NOT NULL COMMENT '课程代码',
              `kcmc` varchar(255) NOT NULL COMMENT '课程名称',
              `courseSetId` int(10) unsigned COMMENT '课程ID',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`),
              UNIQUE `czie_api_course_kcdm` (`kcdm`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '接口学科表';

            CREATE TABLE IF NOT EXISTS `czie_faculty` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `code` varchar(255) NOT NULL COMMENT '院系编号',
              `name`  varchar(255) NOT NULL COMMENT '院系名称',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '院系表';

            CREATE TABLE IF NOT EXISTS `czie_major` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `code` varchar(255) NOT NULL COMMENT '专业编号',
              `name`  varchar(255) NOT NULL COMMENT '专业名称',
              `facultyId` int(10) unsigned COMMENT '院系id',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '专业表';

            CREATE TABLE IF NOT EXISTS `czie_classroom` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `code` varchar(255) NOT NULL COMMENT '班级编号',
              `name`  varchar(255) NOT NULL COMMENT '班级名称',
              `majorId` int(10) unsigned COMMENT '专业id',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '班级表';

            CREATE TABLE IF NOT EXISTS `czie_student` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `xh` varchar(255) NOT NULL COMMENT '学号',
              `xm` varchar(255) NOT NULL COMMENT '姓名',
              `xbdm` varchar(255)  COMMENT '性别代码',
              `yx` varchar(255)  COMMENT '邮箱',
              `yxdm` varchar(255) COMMENT '院系代码',
              `yxmc` varchar(255) COMMENT '院系名称',
              `zydm` varchar(255) COMMENT '专业代码',
              `zymc` varchar(255) COMMENT '专业名称',
              `bh` varchar(255) COMMENT '班号',
              `bjmc` varchar(255) COMMENT '班级名称',
              `rxnf` varchar(255) COMMENT '入学年分',
              `xz` varchar(255) COMMENT '学制',
              `xjzt` varchar(255) COMMENT '学籍状态',
              `gxsj` varchar(255) COMMENT '更新时间',
              `jlzt`  varchar(255) COMMENT '推送状态',
              `px` varchar(255) COMMENT '排序',
              `classroomId`int(10) unsigned COMMENT '班级id',
              `majorId` int(10) unsigned COMMENT '专业id',
              `facultyId` int(10) unsigned COMMENT '院系id',
              `userId` int(10) unsigned COMMENT '对应用户id',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '学生表';

            CREATE TABLE IF NOT EXISTS `czie_api_teacher_org` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `department_key` varchar(255) COMMENT '部门代码',
              `department_name` varchar(255) COMMENT '部门名称',
              `parent_key` varchar(255) COMMENT '所属上级代码',
              `division_key` varchar(255) COMMENT '部门大类',
              `division_name` varchar(255) COMMENT '部门大类名称',
              `remark` varchar(1024) COMMENT '部门大类名称',
              `sort_num` varchar(255) COMMENT '排序码',
              `orgId` int(10) unsigned COMMENT '对应组织机构id',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '教师组织机构表';

            CREATE TABLE IF NOT EXISTS `czie_teacher` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `zgh` varchar(255) COMMENT '职工号',
              `xm` varchar(255) COMMENT '姓名',
              `xb` varchar(255) COMMENT '性别',
              `szbmm` varchar(255) COMMENT '所在部门编号',
              `yx` varchar(255) COMMENT '邮箱',
              `dqzt` varchar(255) COMMENT '在职状态',
              `gxsj` varchar(255) COMMENT '更新时间',
              `jlzt` varchar(255) COMMENT '推送状态',
              `sortnum` varchar(255) COMMENT '排序',
              `userId` int(10) unsigned COMMENT '对应用户id',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '教师表';

            CREATE TABLE IF NOT EXISTS `czie_api_course` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `xq` varchar(255) COMMENT '学期',
              `kcdm` varchar(255) COMMENT '课程代码',
              `kch` varchar(255) COMMENT '课程号',
              `kcmc` varchar(255) COMMENT '课程名称',
              `lbdh` varchar(255) COMMENT '类别代码',
              `zxs` varchar(255) COMMENT '周学时',
              `xs` varchar(255) COMMENT '学时',
              `xf` varchar(255) COMMENT '学分',
              `syxs` varchar(255) COMMENT '实验学时',
              `sjxs` varchar(255) COMMENT '上机学时',
              `jsmc` varchar(255) COMMENT '教师名称',
              `jsdm` varchar(255) COMMENT '教师代码',
              `hbs` varchar(255) COMMENT '合班数',
              `hb` varchar(255) COMMENT '合班',
              `skjs` varchar(255) COMMENT '授课教师',
              `skbj` varchar(255) COMMENT '上课班级名称',
              `lx` varchar(255) COMMENT '类型',
              `lbdm` varchar(255) COMMENT '类别代码',
              `ksfs` varchar(255) COMMENT '考核方式',
              `zjjs` varchar(255) COMMENT '主讲教师标示1，主讲，2 辅带',
              `jssf` varchar(255) COMMENT '教师身份',
              `xkrs` varchar(255),
              `kcxbdm` varchar(255),
              `kcdgbh` varchar(255),
              `api_id` varchar(255),
              `courseId` int(10) unsigned COMMENT '对应courseid',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '排课表';

            CREATE TABLE IF NOT EXISTS `czie_api_course_member` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `xq` varchar(255) COMMENT '学期',
              `bh` varchar(255) COMMENT '班号',
              `bj` varchar(255) COMMENT '班级名称',
              `xh` varchar(255) COMMENT '学号',
              `xm` varchar(255) COMMENT '姓名',
              `kcdm` varchar(255) COMMENT '课程代码',
              `kcmc` varchar(255) COMMENT '课程名称',
              `lbdh` varchar(255) COMMENT '类别代码',
              `kcxh` varchar(255),
              `xs` varchar(255) COMMENT '学时',
              `xf` varchar(255) COMMENT '学分',
              `xb` varchar(255) COMMENT '性别',
              `courseId` int(10) unsigned,
              `userId` int(10) unsigned,
              `memberId` int(10) unsigned COMMENT '对应成员id',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
              `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT '课程成员表';
        ";
        $container = $this->getContainer();
        $container['db']->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $biz['db']->exec('drop table czie_api_course_set');
        $biz['db']->exec('drop table czie_api_course');
        $biz['db']->exec('drop table czie_api_course_member');
        $biz['db']->exec('drop table czie_faculty');
        $biz['db']->exec('drop table czie_major');
        $biz['db']->exec('drop table czie_classroom');
        $biz['db']->exec('drop table czie_api_student');
        $biz['db']->exec('drop table czie_api_teacher_org');
        $biz['db']->exec('drop table czie_api_teacher');
    }
}
