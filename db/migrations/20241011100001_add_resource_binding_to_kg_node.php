<?php
/**
 * 知识图谱节点表 - 添加资源绑定字段
 * 
 * 功能：扩展kg_knowledge_node表，支持节点绑定到课程资源
 * 日期：2024-10-11
 * 
 * 新增字段：
 * - resource_bindings: 资源绑定数据（JSON格式）
 * - primary_resource_type: 主要资源类型（chapter/lesson/assignment等）
 * - primary_resource_id: 主要资源ID
 */

use Phinx\Migration\AbstractMigration;

class AddResourceBindingToKgNode extends AbstractMigration
{
    /**
     * 执行迁移
     */
    public function up()
    {
        $table = $this->table('kg_knowledge_node');
        
        // 添加资源绑定相关字段
        if (!$table->hasColumn('resource_bindings')) {
            $table->addColumn('resource_bindings', 'text', [
                'null' => true,
                'comment' => '资源绑定数据(JSON格式)',
                'after' => 'chapter_id'
            ])->update();
        }
        
        if (!$table->hasColumn('primary_resource_type')) {
            $table->addColumn('primary_resource_type', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => 'chapter',
                'comment' => '主要资源类型: chapter/lesson/assignment/quiz',
                'after' => 'resource_bindings'
            ])->update();
        }
        
        if (!$table->hasColumn('primary_resource_id')) {
            $table->addColumn('primary_resource_id', 'integer', [
                'null' => true,
                'comment' => '主要资源ID',
                'after' => 'primary_resource_type'
            ])->update();
        }
        
        // 添加索引以提高查询性能
        if (!$table->hasIndex(['primary_resource_type', 'primary_resource_id'])) {
            $table->addIndex(['primary_resource_type', 'primary_resource_id'], [
                'name' => 'idx_primary_resource'
            ])->update();
        }
        
        $this->execute("
            ALTER TABLE `kg_knowledge_node` 
            COMMENT = '知识图谱节点表(支持资源绑定)'
        ");
    }

    /**
     * 回滚迁移
     */
    public function down()
    {
        $table = $this->table('kg_knowledge_node');
        
        // 删除索引
        if ($table->hasIndex(['primary_resource_type', 'primary_resource_id'])) {
            $table->removeIndex(['primary_resource_type', 'primary_resource_id'])->update();
        }
        
        // 删除字段
        if ($table->hasColumn('primary_resource_id')) {
            $table->removeColumn('primary_resource_id')->update();
        }
        
        if ($table->hasColumn('primary_resource_type')) {
            $table->removeColumn('primary_resource_type')->update();
        }
        
        if ($table->hasColumn('resource_bindings')) {
            $table->removeColumn('resource_bindings')->update();
        }
    }
}

