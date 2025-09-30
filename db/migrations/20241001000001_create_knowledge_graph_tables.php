<?php
/**
 * 知识图谱系统数据库迁移
 * 创建知识节点表和知识关系表
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class CreateKnowledgeGraphTables extends AbstractMigration
{
    /**
     * 执行迁移
     */
    public function up()
    {
        // 创建知识节点表
        $this->createKnowledgeNodeTable();
        
        // 创建知识关系表  
        $this->createKnowledgeRelationTable();
    }

    /**
     * 回滚迁移
     */
    public function down()
    {
        $this->table('kg_knowledge_relation')->drop()->save();
        $this->table('kg_knowledge_node')->drop()->save();
    }

    /**
     * 创建知识节点表
     */
    private function createKnowledgeNodeTable()
    {
        $table = $this->table('kg_knowledge_node', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '知识图谱节点表'
        ]);

        $table->addColumn('name', 'string', [
            'limit' => 100,
            'null' => false,
            'comment' => '节点名称'
        ])
        ->addColumn('type', 'enum', [
            'values' => ['concept', 'topic', 'skill', 'course'],
            'null' => false,
            'comment' => '节点类型：concept-概念,topic-主题,skill-技能,course-课程'
        ])
        ->addColumn('description', 'text', [
            'null' => true,
            'comment' => '节点描述'
        ])
        ->addColumn('properties', 'json', [
            'null' => true,
            'comment' => '扩展属性JSON'
        ])
        ->addColumn('course_id', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '关联课程ID'
        ])
        ->addColumn('chapter_id', 'integer', [
            'null' => true,
            'comment' => '关联章节ID'
        ])
        ->addColumn('position_x', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'null' => true,
            'default' => 0,
            'comment' => '图形位置X坐标'
        ])
        ->addColumn('position_y', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'null' => true,
            'default' => 0,
            'comment' => '图形位置Y坐标'
        ])
        ->addColumn('style_config', 'json', [
            'null' => true,
            'comment' => '节点样式配置JSON'
        ])
        ->addColumn('weight', 'decimal', [
            'precision' => 3,
            'scale' => 2,
            'null' => false,
            'default' => 1.00,
            'comment' => '节点权重'
        ])
        ->addColumn('status', 'enum', [
            'values' => ['draft', 'published', 'archived'],
            'null' => false,
            'default' => 'draft',
            'comment' => '状态：draft-草稿,published-已发布,archived-已归档'
        ])
        ->addColumn('sort_order', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '排序顺序'
        ])
        ->addColumn('created_by', 'integer', [
            'null' => false,
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
        ->addIndex(['course_id'], ['name' => 'idx_course_id'])
        ->addIndex(['type'], ['name' => 'idx_type'])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->addIndex(['created_by'], ['name' => 'idx_created_by'])
        ->addIndex(['create_time'], ['name' => 'idx_create_time'])
        ->addIndex(['name', 'course_id'], ['name' => 'idx_name_course'])
        ->create();
    }

    /**
     * 创建知识关系表
     */
    private function createKnowledgeRelationTable()
    {
        $table = $this->table('kg_knowledge_relation', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '知识图谱关系表'
        ]);

        $table->addColumn('from_node_id', 'integer', [
            'null' => false,
            'comment' => '起始节点ID'
        ])
        ->addColumn('to_node_id', 'integer', [
            'null' => false,
            'comment' => '目标节点ID'
        ])
        ->addColumn('relation_type', 'enum', [
            'values' => ['prerequisite', 'contains', 'related', 'suggests', 'extends'],
            'null' => false,
            'comment' => '关系类型：prerequisite-前置,contains-包含,related-相关,suggests-建议,extends-扩展'
        ])
        ->addColumn('weight', 'decimal', [
            'precision' => 3,
            'scale' => 2,
            'null' => false,
            'default' => 1.00,
            'comment' => '关系权重'
        ])
        ->addColumn('description', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => '关系描述'
        ])
        ->addColumn('properties', 'json', [
            'null' => true,
            'comment' => '关系扩展属性JSON'
        ])
        ->addColumn('style_config', 'json', [
            'null' => true,
            'comment' => '关系样式配置JSON'
        ])
        ->addColumn('status', 'enum', [
            'values' => ['active', 'inactive'],
            'null' => false,
            'default' => 'active',
            'comment' => '状态：active-启用,inactive-禁用'
        ])
        ->addColumn('created_by', 'integer', [
            'null' => false,
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
        ->addIndex(['from_node_id'], ['name' => 'idx_from_node'])
        ->addIndex(['to_node_id'], ['name' => 'idx_to_node'])
        ->addIndex(['relation_type'], ['name' => 'idx_relation_type'])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->addIndex(['created_by'], ['name' => 'idx_created_by'])
        ->addIndex(['from_node_id', 'to_node_id'], ['name' => 'idx_node_pair'])
        ->addIndex(['from_node_id', 'relation_type'], ['name' => 'idx_from_type'])
        ->addIndex(['to_node_id', 'relation_type'], ['name' => 'idx_to_type'])
        ->addForeignKey('from_node_id', 'kg_knowledge_node', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_relation_from_node'
        ])
        ->addForeignKey('to_node_id', 'kg_knowledge_node', 'id', [
            'delete' => 'CASCADE', 
            'update' => 'CASCADE',
            'constraint' => 'fk_relation_to_node'
        ])
        ->create();
    }
}
