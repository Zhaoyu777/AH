<?php

use Phpmig\Migration\Migration;

class StudentGroupType extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        if (!$this->isFieldExist('czie_student_group', 'type')) {
            $biz['db']->exec("
                ALTER TABLE czie_student_group ADD `type` varchar(255) DEFAULT NULL COMMENT '分组类型' AFTER `courseId`;
            ");
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->isFieldExist('czie_student_group', 'type')) {
            $biz['db']->exec("
                ALTER TABLE `czie_student_group` DROP COLUMN `type`;
            ");
        }
    }

    protected function isFieldExist($table, $filedName)
    {
        $biz = $this->getContainer();

        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $biz['db']->fetchAssoc($sql);

        return empty($result) ? false : true;
    }
}
