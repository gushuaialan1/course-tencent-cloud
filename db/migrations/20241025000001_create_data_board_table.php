<?php
/**
 * 数据看板系统数据库迁移
 * 创建数据看板统计表
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class CreateDataBoardTable extends AbstractMigration
{
    /**
     * 执行迁移
     */
    public function up()
    {
        $table = $this->table('kg_data_board_stat', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '数据看板统计表'
        ]);

        $table->addColumn('stat_key', 'string', [
            'limit' => 50,
            'null' => false,
            'comment' => '统计项key（course_count, teacher_count, student_count, page_view_count等）'
        ])
        ->addColumn('stat_name', 'string', [
            'limit' => 100,
            'null' => false,
            'comment' => '统计项名称'
        ])
        ->addColumn('real_value', 'biginteger', [
            'null' => false,
            'default' => 0,
            'signed' => false,
            'comment' => '真实统计值'
        ])
        ->addColumn('virtual_value', 'biginteger', [
            'null' => false,
            'default' => 0,
            'signed' => false,
            'comment' => '虚拟增加值'
        ])
        ->addColumn('display_value', 'biginteger', [
            'null' => false,
            'default' => 0,
            'signed' => false,
            'comment' => '最终显示值（real_value + virtual_value）'
        ])
        ->addColumn('unit', 'string', [
            'limit' => 20,
            'null' => true,
            'default' => '',
            'comment' => '单位（个、人、次、万等）'
        ])
        ->addColumn('icon', 'string', [
            'limit' => 50,
            'null' => true,
            'default' => '',
            'comment' => '图标类名'
        ])
        ->addColumn('color', 'string', [
            'limit' => 20,
            'null' => true,
            'default' => '',
            'comment' => '颜色标识'
        ])
        ->addColumn('sort_order', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '排序权重'
        ])
        ->addColumn('is_visible', 'boolean', [
            'null' => false,
            'default' => true,
            'comment' => '是否显示'
        ])
        ->addColumn('description', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => '统计项描述'
        ])
        ->addColumn('created_at', 'timestamp', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '创建时间'
        ])
        ->addColumn('updated_at', 'timestamp', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'update' => 'CURRENT_TIMESTAMP',
            'comment' => '更新时间'
        ])
        ->addIndex(['stat_key'], ['unique' => true, 'name' => 'idx_stat_key'])
        ->addIndex(['sort_order'], ['name' => 'idx_sort_order'])
        ->addIndex(['is_visible'], ['name' => 'idx_is_visible'])
        ->create();

        // 插入初始数据
        $this->insertDefaultData();
    }

    /**
     * 回滚迁移
     */
    public function down()
    {
        $this->table('kg_data_board_stat')->drop()->save();
    }

    /**
     * 插入默认统计项
     */
    private function insertDefaultData()
    {
        $data = [
            [
                'stat_key' => 'course_count',
                'stat_name' => '课程总数',
                'real_value' => 0,
                'virtual_value' => 0,
                'display_value' => 0,
                'unit' => '门',
                'icon' => 'layui-icon-read',
                'color' => 'blue',
                'sort_order' => 1,
                'is_visible' => 1,
                'description' => '平台所有已发布课程的总数量',
            ],
            [
                'stat_key' => 'teacher_count',
                'stat_name' => '师资力量',
                'real_value' => 0,
                'virtual_value' => 0,
                'display_value' => 0,
                'unit' => '位',
                'icon' => 'layui-icon-username',
                'color' => 'green',
                'sort_order' => 2,
                'is_visible' => 1,
                'description' => '平台所有教师用户的总数量',
            ],
            [
                'stat_key' => 'student_count',
                'stat_name' => '学员总数',
                'real_value' => 0,
                'virtual_value' => 0,
                'display_value' => 0,
                'unit' => '人',
                'icon' => 'layui-icon-group',
                'color' => 'orange',
                'sort_order' => 3,
                'is_visible' => 1,
                'description' => '平台注册学员的总数量',
            ],
            [
                'stat_key' => 'page_view_count',
                'stat_name' => '总浏览量',
                'real_value' => 0,
                'virtual_value' => 0,
                'display_value' => 0,
                'unit' => '次',
                'icon' => 'layui-icon-chart',
                'color' => 'red',
                'sort_order' => 4,
                'is_visible' => 1,
                'description' => '平台所有页面的累计浏览量',
            ],
            [
                'stat_key' => 'learning_count',
                'stat_name' => '学习人次',
                'real_value' => 0,
                'virtual_value' => 0,
                'display_value' => 0,
                'unit' => '人次',
                'icon' => 'layui-icon-survey',
                'color' => 'cyan',
                'sort_order' => 5,
                'is_visible' => 1,
                'description' => '学员学习课程的总人次',
            ],
            [
                'stat_key' => 'review_count',
                'stat_name' => '评价数量',
                'real_value' => 0,
                'virtual_value' => 0,
                'display_value' => 0,
                'unit' => '条',
                'icon' => 'layui-icon-rate',
                'color' => 'purple',
                'sort_order' => 6,
                'is_visible' => 1,
                'description' => '课程评价的总数量',
            ],
        ];

        $this->table('kg_data_board_stat')->insert($data)->save();
    }
}

