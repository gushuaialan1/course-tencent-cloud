<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

use Phinx\Migration\AbstractMigration;

final class CreateDataBoardCourseStatTable extends AbstractMigration
{
    /**
     * 创建课程数据看板统计表
     */
    public function change()
    {
        $table = $this->table('data_board_course_stat', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '数据看板-课程统计表',
        ]);

        $table->addColumn('id', 'integer', ['signed' => false, 'identity' => true])
            ->addColumn('course_id', 'integer', ['signed' => false, 'comment' => '课程ID'])
            ->addColumn('stat_key', 'string', ['limit' => 50, 'comment' => '统计项key'])
            ->addColumn('stat_name', 'string', ['limit' => 100, 'comment' => '统计项名称'])
            ->addColumn('real_value', 'biginteger', ['signed' => false, 'default' => 0, 'comment' => '真实统计值'])
            ->addColumn('virtual_value', 'biginteger', ['signed' => false, 'default' => 0, 'comment' => '虚拟增加值'])
            ->addColumn('display_value', 'biginteger', ['signed' => false, 'default' => 0, 'comment' => '最终显示值'])
            ->addColumn('unit', 'string', ['limit' => 20, 'default' => '', 'comment' => '单位'])
            ->addColumn('icon', 'string', ['limit' => 50, 'default' => '', 'comment' => '图标类名'])
            ->addColumn('color', 'string', ['limit' => 20, 'default' => '', 'comment' => '颜色标识'])
            ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => '排序权重'])
            ->addColumn('is_visible', 'boolean', ['default' => 1, 'comment' => '是否显示'])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'comment' => '统计项描述'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => '创建时间'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'comment' => '更新时间'])
            ->addIndex(['course_id', 'stat_key'], ['unique' => true, 'name' => 'idx_course_stat_key'])
            ->addIndex(['course_id'], ['name' => 'idx_course_id'])
            ->addIndex(['sort_order'], ['name' => 'idx_sort_order'])
            ->addIndex(['is_visible'], ['name' => 'idx_is_visible'])
            ->create();
    }
}

