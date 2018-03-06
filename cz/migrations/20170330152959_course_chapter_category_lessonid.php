<?php

use Phpmig\Migration\Migration;

class CourseChapterCategoryLessonid extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();

        if (!$this->fieldExists('course_chapter', 'lessonId')) {
            $biz['db']->exec('
                ALTER TABLE `course_chapter` ADD `lessonId` int(10) DEFAULT 0 AFTER `courseId` ;
            ');
        }

        if (!$this->fieldExists('course_chapter', 'stage')) {
            $biz['db']->exec('
                ALTER TABLE `course_chapter` ADD `stage` ENUM("before","in","after") NOT NULL DEFAULT "in" COMMENT "状态" AFTER `type`;
            ');
        }

        if (!$this->fieldExists('czie_course_lesson_chapter', 'categoryId')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson_chapter` ADD `categoryId` int(10) DEFAULT 0 AFTER `lessonId` ;
            ');
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();

        if ($this->fieldExists('course_chapter', 'lessonId')) {
            $biz['db']->exec('
                ALTER TABLE `course_chapter` DROP COLUMN `lessonId`;
            ');
        }

        if ($this->fieldExists('course_chapter', 'stage')) {
            $biz['db']->exec('
                ALTER TABLE `course_chapter` DROP COLUMN `stage`;
            ');
        }

        if ($this->fieldExists('czie_course_lesson_chapter', 'categoryId')) {
            $biz['db']->exec('
                ALTER TABLE `czie_course_lesson_chapter` DROP COLUMN `categoryId`;
            ');
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
