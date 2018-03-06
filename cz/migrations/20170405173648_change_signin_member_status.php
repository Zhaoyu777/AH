<?php

use Phpmig\Migration\Migration;

class ChangeSigninMemberStatus extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $db = $biz['db'];
        if ($this->fieldExists('czie_lesson_signin_member', 'status')) {
            $db->exec("ALTER TABLE `czie_lesson_signin_member` CHANGE `status` `status` ENUM('absent','attend','late','early','leave') DEFAULT 'absent' COMMENT '出勤类型：absent为缺勤，attent为出勤，late为迟到，early为早退，leave为请假。'");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $db = $biz['db'];
        if ($this->fieldExists('czie_lesson_signin_member', 'status')) {
            $db->exec("ALTER TABLE `czie_lesson_signin_member` CHANGE `status` `status` ENUM('absent','attend') DEFAULT 'absent' COMMENT '出勤类型：absent为缺勤，attent为出勤。'");
        }
    }

    private function fieldExists($table, $field)
    {
        $biz = $this->getContainer();
        $sql = "DESCRIBE `{$table}` `{$field}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
