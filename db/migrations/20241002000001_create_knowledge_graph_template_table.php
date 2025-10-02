<?php
/**
 * 知识图谱模板系统数据库迁移
 * 创建知识图谱模板表
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class CreateKnowledgeGraphTemplateTable extends AbstractMigration
{
    /**
     * 执行迁移
     */
    public function up()
    {
        $this->createKnowledgeGraphTemplateTable();
    }

    /**
     * 回滚迁移
     */
    public function down()
    {
        $this->table('kg_knowledge_graph_template')->drop()->save();
    }

    /**
     * 创建知识图谱模板表
     */
    private function createKnowledgeGraphTemplateTable()
    {
        $table = $this->table('kg_knowledge_graph_template', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '知识图谱模板表'
        ]);

        $table->addColumn('name', 'string', [
            'limit' => 100,
            'null' => false,
            'comment' => '模板名称'
        ])
        ->addColumn('category', 'string', [
            'limit' => 50,
            'null' => false,
            'comment' => '模板分类：cs-计算机科学,math-数学,language-语言,business-商业,other-其他'
        ])
        ->addColumn('description', 'text', [
            'null' => true,
            'comment' => '模板描述'
        ])
        ->addColumn('preview_image', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => '预览图片URL'
        ])
        ->addColumn('node_data', 'text', [
            'null' => false,
            'comment' => '节点数据JSON'
        ])
        ->addColumn('relation_data', 'text', [
            'null' => true,
            'comment' => '关系数据JSON'
        ])
        ->addColumn('node_count', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '节点数量'
        ])
        ->addColumn('relation_count', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '关系数量'
        ])
        ->addColumn('difficulty_level', 'enum', [
            'values' => ['beginner', 'intermediate', 'advanced'],
            'null' => false,
            'default' => 'beginner',
            'comment' => '难度级别：beginner-初级,intermediate-中级,advanced-高级'
        ])
        ->addColumn('tags', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => '标签（逗号分隔）'
        ])
        ->addColumn('is_system', 'boolean', [
            'null' => false,
            'default' => false,
            'comment' => '是否系统预置模板'
        ])
        ->addColumn('is_active', 'boolean', [
            'null' => false,
            'default' => true,
            'comment' => '是否启用'
        ])
        ->addColumn('usage_count', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '使用次数'
        ])
        ->addColumn('sort_order', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '排序顺序'
        ])
        ->addColumn('created_by', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '创建者用户ID'
        ])
        ->addColumn('updated_by', 'integer', [
            'null' => true,
            'comment' => '更新者用户ID'
        ])
        ->addColumn('create_time', 'integer', [
            'null' => false,
            'comment' => '创建时间戳'
        ])
        ->addColumn('update_time', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '更新时间戳'
        ])
        ->addIndex(['category'], ['name' => 'idx_category'])
        ->addIndex(['difficulty_level'], ['name' => 'idx_difficulty'])
        ->addIndex(['is_system'], ['name' => 'idx_is_system'])
        ->addIndex(['is_active'], ['name' => 'idx_is_active'])
        ->addIndex(['usage_count'], ['name' => 'idx_usage_count'])
        ->addIndex(['sort_order'], ['name' => 'idx_sort_order'])
        ->create();
    }
}

