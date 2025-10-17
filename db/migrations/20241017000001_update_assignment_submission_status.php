<?php
/**
 * 修复作业提交表的 status 字段
 * 添加 auto_graded 和 grading 状态，删除冗余的 grade_status 字段
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class UpdateAssignmentSubmissionStatus extends AbstractMigration
{
    /**
     * 执行迁移
     */
    public function up()
    {
        // 使用原生 SQL 修改 status 字段的 ENUM 类型
        $this->execute("
            ALTER TABLE kg_assignment_submission 
            MODIFY COLUMN status ENUM('draft', 'submitted', 'auto_graded', 'grading', 'graded', 'returned') 
            NOT NULL DEFAULT 'draft' 
            COMMENT '提交状态'
        ");

        // 检查 grade_status 字段是否存在，如果存在则删除
        $table = $this->table('kg_assignment_submission');
        if ($table->hasColumn('grade_status')) {
            // 先删除索引
            if ($table->hasIndex(['grade_status'])) {
                $table->removeIndex(['grade_status'])->save();
            }
            
            // 删除字段
            $table->removeColumn('grade_status')->save();
        }
    }

    /**
     * 回滚迁移
     */
    public function down()
    {
        // 恢复原来的 status 定义
        $this->execute("
            ALTER TABLE kg_assignment_submission 
            MODIFY COLUMN status ENUM('draft', 'submitted', 'graded', 'returned') 
            NOT NULL DEFAULT 'draft' 
            COMMENT '提交状态'
        ");

        // 重新添加 grade_status 字段
        $table = $this->table('kg_assignment_submission');
        $table->addColumn('grade_status', 'enum', [
            'values' => ['pending', 'grading', 'completed'],
            'default' => 'pending',
            'null' => false,
            'comment' => '评分状态',
            'after' => 'status'
        ])
        ->addIndex(['grade_status'], ['name' => 'idx_grade_status'])
        ->save();
    }
}

